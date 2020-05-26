<?php
namespace VKHP;


class Method
{
    private static $version = '5.103';


    public static function make(
        ?string $access_token,
        string $method,
        array $params
    ): object {
        $methodUrl = "https://api.vk.com/method/{$method}";
        $params = $params + [ 'access_token' => $access_token, 'v' => self::$version ];

        $request = \VKHP\Request::makeJson($methodUrl, $params);
        $request->ok = isset($request->response);
        return $request;
    }

    public static function messagesSend(string $access_token, array $params): object
    {
        $user_ids = $params['user_ids'] ?? null;
        if (empty($user_ids)) {
            throw new \Exception('field `user_ids` is empty');
        }


        $params['random_id'] = $params['random_id'] ?? 0;
        $user_ids = is_array($user_ids) ? $user_ids : explode(',', $user_ids);
        $user_ids = array_unique(array_filter($user_ids));
        $users_count = count($user_ids);

        [$res, $suc] = [[], 0];
        // j -- количество идов, которые будут взяты
        for ($i = 0, $j = 100, $c = ceil($users_count / $j); $i < $c; $i++) {
            $user_ids_str = implode(',', array_slice($user_ids, $i * $j, $j));
            $params['user_ids'] = $user_ids_str;

            $req = self::make($access_token, 'messages.send', $params);
            if ($req->ok === false) { return $req; }
            foreach ($req->response as $message) {
                if (isset($message->error)) {continue;}

                $res[] = $message;
                $suc += 1;
            }
        }
        return (object) [ 'successful' => $suc, 'response' => $res ];
    }

    public static function uploadMessagesPhoto(
        string $access_token,
        array $files,
        array $params
    ): array {
        if (empty($params['peer_id'])) {
            throw new \Exception('field `peer_id` is empty');
        }

        $gurl = self::make($access_token, 'photos.getMessagesUploadServer', $params);
        if ($gurl->ok === false) { return (array) $gurl; }

        $saved_files = self::saveFiles($files);
        $upload_files = \VKHP\Request::makeJson($gurl->response->upload_url,
            $saved_files['cfiles'], [ 'Content-type: multipart/form-data;charset=utf-8' ]);
        self::deleteFiles($saved_files['paths']);
        if (isset($upload_files->error)) { return (array) $upload_files; }

        $save_files = self::make($access_token, 'photos.saveMessagesPhoto', [
            'server' => $upload_files->server,
            'photo' => $upload_files->photo,
            'hash' => $upload_files->hash
        ] + $params);
        if ($save_files->ok === false) { return (array) $save_files; }

        $attachment = [];
        foreach ($save_files->response as $photo) {
            $attachment[] = "photo{$photo->owner_id}_{$photo->id}";
        }
        return $attachment;
    }

    public static function uploadMessagesDoc(
        string $access_token,
        array $files,
        array $params
    ): array {
        $required_fields = [ 'peer_id', 'type' ];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                throw new \Exception("field `{$field}` is required");
            }
        }

        $gurl = self::make($access_token, 'docs.getMessagesUploadServer', $params);
        if ($gurl->ok === false) { return (array) $gurl; }

        $attachment = [];
        foreach ($files as $file) {
            $saved_file = self::saveFiles([ $file ], true);
            $upload_file = \VKHP\Request::makeJson($gurl->response->upload_url,
                $saved_file['cfiles'], [ 'Content-type: multipart/form-data;charset=utf-8' ]);
            self::deleteFiles($saved_file['paths']);
            if (isset($upload_file->error)) { return (array) $upload_file; }

            $save_file = self::make($access_token, 'docs.save', [
                'file' => $upload_file->file
            ] + $params);
            if ($save_file->ok === false) { return (array) $save_files; }
            if (array_key_exists(0, $save_file->response)) {
                $save_file->response = (object) [ $params['type'] => $save_file->response[0] ];
            }

            $file = $save_file->response->{$params['type']};
            $attachment[] = "doc{$file->owner_id}_{$file->id}";
        }
        return $attachment;
    }


    private static function saveFiles(array $files, bool $single = false): array
    {
        [$paths, $cfiles, $i] = [[], [], 1];
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            if (! file_exists($file)) {
                $paths[] = $fpath = tempnam(sys_get_temp_dir(), 'VKHP');
                if (($contents = file_get_contents($file)) === false) {
                    throw new \Exception("can't retrieve file contents for path '{$file}'");
                }

                file_put_contents($fpath, $contents);
            } else { $fpath = realpath($file); }

            $mime_type = mime_content_type($fpath);
            $cfile = new \CURLFile($fpath, $mime_type, $pathinfo['basename']);

            $cfkey = $single ? 'file' : ('file' . $i++);
            $cfiles[$cfkey] = $cfile;
            if ($single) {break;}
        }
        return [ 'paths' => $paths, 'cfiles' => $cfiles ];
    }

    private static function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

class Request
{
    public static function make(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): string {
        $ch = curl_init();
        $ch_options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
            CURLOPT_HTTPHEADER => $headers ?? [],
            CURLOPT_POST => $fields !== null,
            CURLOPT_POSTFIELDS => is_array($fields) && $headers === null ? http_build_query($fields) : $fields,
        ] + (array) $options;
        curl_setopt_array($ch, $ch_options);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function makeJson(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): object {
        $request = self::make($url, $fields, $headers, $options);
        if (empty($request)) {
            throw new \Exception('response is empty, it is impossible execute json_decode');
        }

        return json_decode($request);
    }
}

class Generator
{
    const WHITE = 'secondary';
    const BLUE = 'primary';
    const GREEN = 'positive';
    const RED = 'negative';

    // keyboard-mode
    const KM_ONETIME = 1 << 0; // one_time
    const KM_INLINE = 1 << 1; // inline


    public static function keyboard(
        array $buttons,
        int $mode = 0
    ): string {
        return json_encode([
            'one_time' => (bool) ($mode & self::KM_ONETIME),
            'inline' => (bool) ($mode & self::KM_INLINE),
            'buttons' => $buttons
        ]);
    }

    public static function button(
        string $label,
        string $color = self::WHITE,
        ?array $payload = null
    ): array {
        return [
            'action' => [
                'type' => 'text',
                'label' => $label,
                'payload' => self::jEncode($payload)
            ],
            'color' => $color
        ];
    }

    public static function buttonLocation(array $payload): array
    {
        return [
            'action' => [
                'type' => 'location',
                'payload' => self::jEncode($payload)
            ]
        ];
    }

    public static function buttonVKPay(string $hash, array $payload): array
    {
        return [
            'action' => [
                'type' => 'vkpay',
                'hash' => $hash,
                'payload' => self::jEncode($payload)
            ]
        ];
    }

    public static function buttonVKApps(
        string $label,
        int $app_id,
        int $owner_id,
        string $hash,
        array $payload
    ): array {
        return [
            'action' => [
                'type' => 'open_app',
                'label' => $label,
                'app_id' => $app_id,
                'owner_id' => $owner_id,
                'hash' => $hash,
                'payload' => self::jEncode($payload)
            ]
        ];
    }


    private static function jEncode($payload)
    {
        return $payload === null ? $payload : json_encode($payload);
    }
}
