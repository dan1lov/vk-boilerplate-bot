<?php

$data = json_decode(file_get_contents('php://input'));
$group_id = (int)($data->group_id ?? 0);

require_once __DIR__ . '/files/setup.php';

if ($data->secret !== $config->secret_key) {
    die('ok');
}

switch ($data->type) {
    case 'confirmation':
        die($config->confirm_token);
    case 'message_new':
        $client = $data->object->client_info;

        $message = $data->object->message;
        $message_lower = mb_strtolower($message->text);

        $peer_id = $message->peer_id;
        $user_id = $message->from_id;
        $user = userGetOrCreate($user_id);

        $payload = isset($message->payload)
            ? json_decode($message->payload)
            : (object)[];
        $payload->c ??= null;

        $temp = VKHP\Scenarios::check($settings->tsf, $user_id, true);
        $is_manager = in_array($user_id, $settings->admin_ids);
        $commands = require_once "$home/files/commands.php";

        foreach ($commands as $key => $cmd) {
            if (($cmd['forAdmin'] && !$is_manager)
                || ($temp !== false && $temp->c !== $key)
                || ($temp === false
                    && $payload->c !== $key
                    && (array_key_exists($payload->c, $commands)
                        || strposArray($message_lower, $cmd['aliases']) !== 0
                    )
                )
            ) {
                continue;
            }

            $parameters = $cmd['execute']();
            break;
        }

        if (empty($parameters)) {
            $parameters = $commands['menu']['execute']();
        }

        VKHP\Method::messagesSend($config->access_token, [
            'peer_id' => $peer_id,
            'message' => $parameters[0] ?? getTemplate('default.null'),
            'keyboard' => $parameters[1] ?? null,
            'attachment' => $parameters[2] ?? null,
            'dont_parse_links' => true,
            'disable_mentions' => true,
        ]);
        break;
    case 'message_event':
        $user_id = $data->object->user_id;
        $user = userGetOrCreate($user_id);

        $temp = VKHP\Scenarios::check($settings->tsf, $user_id, true);
        $payload = $data->object->payload ?? (object)[];
        $cid = $temp->c ?? $payload->c ?? null;

        $is_manager = in_array($user_id, $settings->admin_ids);
        $commands = require_once "$home/files/commands.php";

        if (isset($commands[$cid])) {
            if ($commands[$cid]['forAdmin'] && !$is_manager) {
                die('ok');
            }

            $exec = $commands[$cid]['execute']();
            processingMessageEvent($data->object, generateEventData($exec));
            break;
        }
        break;
}

Danilov\Database::close();
die('ok');
