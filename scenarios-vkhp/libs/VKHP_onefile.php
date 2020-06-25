<?php
namespace VKHP;

/**
 * Class for generating keyboard and any type buttons
 */
class Generator
{
    /**
     * @var string
     */
    const WHITE = 'secondary';

    /**
     * @var string
     */
    const BLUE = 'primary';

    /**
     * @var string
     */
    const GREEN = 'positive';

    /**
     * @var string
     */
    const RED = 'negative';

    /**
     * @var int
     */
    const KM_ONETIME = 1 << 0;

    /**
     * @var int
     */
    const KM_INLINE = 1 << 1;

    /**
     * Generating keyboard
     *
     * @param array   $buttons Array of buttons
     * @param integer $mode    Keyboard mode
     *
     * @return string
     */
    public static function keyboard(array $buttons, int $mode = 0): string
    {
        return json_encode(
            [
                'one_time' => (bool) ($mode & self::KM_ONETIME),
                'inline' => (bool) ($mode & self::KM_INLINE),
                'buttons' => $buttons
            ]
        );
    }

    /**
     * Generate button with type text
     *
     * @param string     $label   Button label
     * @param string     $color   Button color
     * @param array|null $payload Button payload
     *
     * @return array
     */
    public static function button(
        string $label,
        string $color = self::WHITE,
        ?array $payload = null
    ): array {
        return [
            'action' => [
                'type' => 'text',
                'label' => $label,
                'payload' => self::payloadEncode($payload)
            ],
            'color' => $color
        ];
    }

    /**
     * Generate button with type open_link
     *
     * @param string     $label   Button label
     * @param string     $link    Link in button
     * @param array|null $payload Button payload
     *
     * @return array
     */
    public static function buttonLink(
        string $label,
        string $link,
        ?array $payload = null
    ): array {
        return [
            'action' => [
                'type' => 'open_link',
                'link' => $link,
                'label' => $label,
                'payload' => self::payloadEncode($payload)
            ]
        ];
    }

    /**
     * Generate button with type location
     *
     * @param array|null $payload Button payload
     *
     * @return array
     */
    public static function buttonLocation(?array $payload = null): array
    {
        return [
            'action' => [
                'type' => 'location',
                'payload' => self::payloadEncode($payload)
            ]
        ];
    }

    /**
     * Generate button with type vkpay
     *
     * @param string     $hash    Hash for button
     * @param array|null $payload Button payload
     *
     * @return array
     */
    public static function buttonVKPay(string $hash, ?array $payload = null): array
    {
        return [
            'action' => [
                'type' => 'vkpay',
                'hash' => $hash,
                'payload' => self::payloadEncode($payload)
            ]
        ];
    }

    /**
     * Generate button with type open_app
     *
     * @param string     $label    Button label
     * @param integer    $app_id   Application id
     * @param integer    $owner_id Owner id
     * @param string     $hash     Hash for button
     * @param array|null $payload  Button payload
     *
     * @return array
     */
    public static function buttonVKApps(
        string $label,
        int $app_id,
        int $owner_id,
        string $hash,
        ?array $payload = null
    ): array {
        return [
            'action' => [
                'type' => 'open_app',
                'label' => $label,
                'app_id' => $app_id,
                'owner_id' => $owner_id,
                'hash' => $hash,
                'payload' => self::payloadEncode($payload)
            ]
        ];
    }

    /**
     * Encode payload
     *
     * @param array|null $payload Payload
     *
     * @return string
     */
    protected static function payloadEncode(?array $payload): string
    {
        return $payload === null ? '' : json_encode($payload);
    }
}

/**
 * Class for making queries to VK API
 */
class Method
{
    /**
     * @var string
     */
    protected static $version = '5.110';

    /**
     * Make query to VK API
     *
     * @param string $access_token Access token
     * @param string $method       Method name
     * @param array  $params       Parameters for method
     *
     * @return object
     */
    public static function make(
        string $access_token,
        string $method,
        array $params
    ): object {
        $method_url = "https://api.vk.com/method/{$method}";
        $params += [ 'access_token' => $access_token, 'v' => self::$version ];

        $request = \VKHP\Request::makeJson($method_url, $params);
        $request->ok = isset($request->response);
        return $request;
    }

    /**
     * Sending message to community users
     *
     * @param string $access_token Access token
     * @param array  $params       Parameters for messages.send method
     *
     * @return object
     */
    public static function messagesSend(string $access_token, array $params): object
    {
        $params['random_id'] = $params['random_id'] ?? 0;
        $user_ids = $params['user_ids'] ?? null;
        if (empty($user_ids)) {
            return self::make($access_token, 'messages.send', $params);
        }


        $user_ids = is_array($user_ids) ? $user_ids : explode(',', $user_ids);
        $user_ids = array_unique(array_filter($user_ids));
        $users_count = count($user_ids);

        [$res, $suc] = [[], 0];
        // j -- количество идов, которые будут взяты
        for ($i = 0, $j = 100, $c = ceil($users_count / $j); $i < $c; $i++) {
            $user_ids_str = implode(',', array_slice($user_ids, $i * $j, $j));
            $params['user_ids'] = $user_ids_str;

            $req = self::make($access_token, 'messages.send', $params);
            if ($req->ok === false) {
                return $req;
            }

            foreach ($req->response as $message) {
                if (isset($message->error)) {
                    continue;
                }

                $res[] = $message;
                $suc += 1;
            }
        }
        return (object) [ 'successful' => $suc, 'response' => $res ];
    }

    /**
     * Uploading photos to VK
     *
     * @param string $access_token Access token
     * @param array  $files        Files to upload
     * @param array  $params       Parameters for uploading method
     *
     * @throws Exception if peer_id parameter is not specified in $params array
     * @throws Exception if count of files in $files array more than 5
     *
     * @return array
     */
    public static function uploadMessagesPhoto(
        string $access_token,
        array $files,
        array $params
    ): array {
        if (empty($files)) {
            return array();
        }
        if (empty($params['peer_id'])) {
            throw new \Exception('field `peer_id` is empty');
        }
        if (count($files) > 5) {
            throw new \Exception('too much files (>5)');
        }


        $gurl = self::make($access_token, 'photos.getMessagesUploadServer', $params);
        if ($gurl->ok === false) {
            return (array) $gurl;
        }

        $saved_files = self::_saveFiles($files);
        $upload_files = \VKHP\Request::makeJson(
            $gurl->response->upload_url,
            self::_createCURLFiles($saved_files),
            [ 'Content-type: multipart/form-data;charset=utf-8' ]
        );
        self::_deleteFiles($saved_files);
        if (isset($upload_files->error)) {
            return (array) $upload_files;
        }

        $save_files = self::make(
            $access_token,
            'photos.saveMessagesPhoto',
            [
                'server' => $upload_files->server,
                'photo' => $upload_files->photo,
                'hash' => $upload_files->hash
            ] + $params
        );
        if ($save_files->ok === false) {
            return (array) $save_files;
        }

        $attachment = [];
        foreach ($save_files->response as $photo) {
            $attachment[] = "photo{$photo->owner_id}_{$photo->id}";
        }
        return $attachment;
    }

    /**
     * Uploading documents to VK
     *
     * @param string $access_token Access token
     * @param array  $files        Files to upload
     * @param array  $params       Parameters for uploading method
     *
     * @throws Exception if field peer_id/type is not specified in $params array
     *
     * @return array
     */
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
        if ($gurl->ok === false) {
            return (array) $gurl;
        }

        $attachment = [];
        foreach ($files as $file) {
            $saved_file = self::_saveFiles([ $file ]);
            $upload_file = \VKHP\Request::makeJson(
                $gurl->response->upload_url,
                self::_createCURLFiles($saved_file, true),
                [ 'Content-type: multipart/form-data;charset=utf-8' ]
            );
            self::_deleteFiles($saved_file);
            if (isset($upload_file->error)) {
                return (array) $upload_file;
            }

            $save_file = self::make(
                $access_token,
                'docs.save',
                [
                    'file' => $upload_file->file
                ] + $params
            );
            if ($save_file->ok === false) {
                return (array) $save_files;
            }
            if (array_key_exists(0, $save_file->response)) {
                $save_file->response = (object) [
                    $params['type'] => $save_file->response[0]
                ];
            }

            $file = $save_file->response->{$params['type']};
            $attachment[] = "doc{$file->owner_id}_{$file->id}";
        }
        return $attachment;
    }

    /**
     * Saving files in temporary folder
     *
     * @param array $files Files to saving
     * @param array $paths Array of paths
     *
     * @throws Exception if can't retrieve file contents for a certain path
     *
     * @return array
     */
    public static function _saveFiles(array $files, array $paths = []): array
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                $paths[] = realpath($file);
                continue;
            }

            $paths[] = $fpath = tempnam(sys_get_temp_dir(), 'VKHP');
            if (($contents = file_get_contents($file)) === false) {
                throw new \Exception("can't retrieve file contents for path '{$file}'");
            }
            file_put_contents($fpath, $contents);
        }
        return $paths;
    }

    /**
     * Creating CURLFile objects for sending in CURLOPT_POSTFIELDS
     *
     * @param array   $paths      Array of paths
     * @param boolean $single     Flag for single uploading
     * @param string  $field_name Field name in post fields
     *
     * @return array
     */
    public static function _createCURLFiles(
        array $paths,
        bool $single = false,
        string $field_name = 'file'
    ): array {
        [$cfiles, $i] = [[], 1];
        $mime_types = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'text/plain' => '.txt'
        ];

        foreach ($paths as $path) {
            $pathinfo = pathinfo($path);
            $mime_type = mime_content_type($path);

            $basename = in_array($mime_type, array_flip($mime_types))
                ? $pathinfo['filename'] . $mime_types[$mime_type]
                : $pathinfo['basename'];

            $cfile = new \CURLFile($path, $mime_type, $basename);
            $cfkey = $single ? $field_name : ($field_name . $i++);

            $cfiles[$cfkey] = $cfile;
            if ($single) {
                break;
            }
        }
        return $cfiles;
    }

    /**
     * Delete files from paths in $paths array.
     *
     * If $delete_all is set to TRUE, then even files that
     * were not saved to the temporary directory will be deleted
     *
     * @param array   $paths      Array of paths to deleting
     * @param boolean $delete_all Flag to delete all files in $paths
     *
     * @return void
     */
    public static function _deleteFiles(
        array $paths,
        bool $delete_all = false
    ): void {
        foreach ($paths as $path) {
            // realpath нужен во избежание проблем на windows,
            // чтобы обратные слеши (\) заменились на обычные слеши (/)
            $temp_path = realpath(sys_get_temp_dir());
            if (! $delete_all && mb_strpos($path, $temp_path) !== 0) {
                continue;
            }

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

/**
 * Class for making curl requests
 */
class Request
{
    /**
     * Make curl request
     *
     * @param string     $url     URL
     * @param array|null $fields  Post fields
     * @param array|null $headers Headers
     * @param array|null $options Additional options
     *
     * @return string
     */
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
        ] + (array) $options;
        if ($fields !== null) {
            $ch_options[CURLOPT_POST] = true;
            $ch_options[CURLOPT_POSTFIELDS] = !$headers
                ? http_build_query($fields)
                : $fields;
        }
        curl_setopt_array($ch, $ch_options);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * Same as make(), but returned value goes through json_decode
     *
     * @param string     $url     URL
     * @param array|null $fields  Post fields
     * @param array|null $headers Headers
     * @param array|null $options Additional options
     *
     * @throws Exception if esponse is empty or cannot be decoded
     *
     * @return object
     */
    public static function makeJson(
        string $url,
        ?array $fields = null,
        ?array $headers = null,
        ?array $options = null
    ): object {
        $request = self::make($url, $fields, $headers, $options);
        $decoded = json_decode($request);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('response is empty or cannot be decoded');
        }

        return $decoded;
    }
}

/**
 * Class for manage properties in temporary file
 */
class Scenarios
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $data;

    /**
     * Method for checking existing temporary file
     *
     * @param string  $temp_folder Path to temporary folder
     * @param integer $id          Unique id
     * @param boolean $return      Flag for returning object
     *
     * @return void
     */
    public static function check(string $temp_folder, int $id, bool $return = false)
    {
        if (! file_exists("{$temp_folder}/file_id{$id}.json")) {
            return false;
        } elseif (! $return) {
            return true;
        } else {
            return new self($temp_folder, $id);
        }
    }

    public function __construct(string $temp_folder, string $id, array $data = [])
    {
        if (! file_exists($temp_folder)) {
            return false;
        }

        $this->id = $id;
        $this->file = "{$temp_folder}/file_id{$id}.json";
        $this->data = file_exists($this->file)
            ? json_decode(file_get_contents($this->file), true)
            : $data;
        
        if (file_exists($this->file) && isset($this->data['__onetime'])) {
            $this->clear();
        }
    }

    /**
     * Saving data in temporary file
     *
     * @return boolean
     */
    public function save(): bool
    {
        $encoded_data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        $result_of_saving = file_put_contents($this->file, $encoded_data);
        return !is_bool($result_of_saving);
    }

    /**
     * Delete temporary file
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return file_exists($this->file) ? unlink($this->file) : true;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
