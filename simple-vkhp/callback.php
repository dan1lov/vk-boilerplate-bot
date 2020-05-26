<?php
if (! isset($_POST)) { die('ok'); }

$data = json_decode(file_get_contents( 'php://input' ));
$group_id = (int) ($data->group_id ?? 0);

require_once __DIR__ . '/files/setup.php';
use \VKHP\Method as VKHPM;

if ($data->type === 'confirmation') { die($config['confirm_token']); }
// if ($data->secret !== $config['secret_key']) { die('ok'); }


$object = $data->object;
switch ($data->type) {
    case 'message_new':
        $client_info = $object->client_info;
        $message_obj = $object->message;

        $peer_id = $message_obj->peer_id;
        $user_id = $message_obj->from_id;

        $message = mb_strtolower($message_obj->text);
        $m_parts = explode(' ', $message);

        $commands = require_once "{$home}/files/commands.php";
        $parameters = [];
        foreach ($commands as $key => $command) {
            if (strpos_array($message, $command['aliases']) !== 0) {continue;}

            $parameters = $command['exec']($object, $config, $user_id);
        }

        if (! empty($parameters)) {
            VKHPM::messagesSend($config['access_token'], [
                'user_ids' => $peer_id,
                'message' => $parameters[0] ?? 'null',
                'keyboard' => $parameters[1] ?? null,
                'attachment' => $parameters[2] ?? null,
                'random_id' => 0
            ]);
        }
        break;
}

die('ok');
