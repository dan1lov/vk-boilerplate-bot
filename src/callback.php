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

        $payload = json_decode($message->payload ?? '') ?: (object)[];
        $payload->c ??= null;

        $temp = VKHP\Scenarios::check($settings->tsf, $user_id, true);
        $is_manager = in_array($user_id, $settings->manager_ids);
        $commands = require_once "$home/files/commands.php";

        foreach ($commands as $key => $command) {
            $deny = $command['forManager'] && !$is_manager;
            $strict = $temp !== false && $temp->c !== $key;
            $wrong_key = $payload->c !== $key;

            $payload_exists = array_key_exists($payload->c, $commands);
            $word_trigger = strposArray($message_lower, $command['aliases']) !== 0;
            $correct_this = $payload_exists || $word_trigger;
            $not_this = $temp === false && $wrong_key && $correct_this;

            if ($deny || $strict || $not_this) {
                continue;
            }

            $parameters = $command['execute']();
            break;
        }

        if (empty($parameters)) {
            $parameters = $commands['menu']['execute']();
        }

        sendMessageObjectTo($peer_id, validateMessageArray($parameters));
        break;
    case 'message_event':
        $user_id = $data->object->user_id;
        $user = userGetOrCreate($user_id);

        $temp = VKHP\Scenarios::check($settings->tsf, $user_id, true);
        $payload = $data->object->payload ?? (object)[];
        $cid = $temp->c ?? $payload->c ?? null;

        $is_manager = in_array($user_id, $settings->manager_ids);
        $commands = require_once "$home/files/commands.php";

        if (isset($commands[$cid])) {
            if ($commands[$cid]['forManager'] && !$is_manager) {
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
