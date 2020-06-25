<?php
if (! isset($_POST)) { die('ok'); }
$data = json_decode(file_get_contents( 'php://input' ));

require_once 'config.php';
require_once 'functions.php';

// if ($data->secret !== $config['secret_key']) { die('ok'); }
if ($data->type === 'confirmation') { die($config['confirm_token']); }


$object = $data->object;
switch ($data->type) {
    case 'message_new':
        $client_info = $object->client_info;
        $message_obj = $object->message;

        $peer_id = $message_obj->peer_id;
        $user_id = $message_obj->from_id;

        $message = mb_strtolower($message_obj->text);
        $m_parts = explode(' ', $message);

        require_once 'commands.php';

        if (isset($answer)) {
            api('messages.send', [
                'peer_id' => $peer_id,
                'message' => $answer,
                'keyboard' => $keyboard ?? null,
                'attachment' => $attachment ?? null,
                'random_id' => 0,
                'access_token' => $config['access_token'],
                'v' => $config['v'],
            ]);
        }
        break;
}
die('ok');
