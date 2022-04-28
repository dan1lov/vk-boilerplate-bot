<?php

use VKHP\Method as VKHPM;
use Danilov\Database as DB;

# -- user
function userGetOrCreate(int $user_id): object {
    if ($user_id <= 0) {
        die('ok');
    }

    $sql = "SELECT * FROM users WHERE user_id = ?";
    $user = DB::getRow($sql, [$user_id]);

    if (empty($user)) {
        DB::execute("INSERT INTO users (user_id) VALUES (?)", [$user_id]);
        $user = DB::getRow($sql, [$user_id]);
    }

    return (object)$user;
}

function getUserField(string $field, int $user_id) {
    return DB::getOne("SELECT $field FROM users WHERE user_id = ?", [$user_id]);
}

function changeUserField(string $field, int $user_id, $val): void {
    $fs = is_int($val) ? "$field = $field + ?" : "$field = ?";
    DB::execute("UPDATE users SET $fs WHERE user_id = ?", [$val, $user_id]);
}

# -- events
function processingMessageEvent(object $object, array $event_data): bool {
    if (!isset($event_data['type'])) {
        return messageEditOrSend(
            $object->user_id,
            $event_data,
            $object->conversation_message_id
        );
    }

    return VKHPM::make(
            $GLOBALS['config']->access_token,
            'messages.sendMessageEventAnswer',
            [
                'event_id' => $object->event_id,
                'user_id' => $object->user_id,
                'peer_id' => $object->peer_id,
                'event_data' => json_encode($event_data)
            ]
        )->ok ?? false;
}

function generateEventData(array $res): array {
    $types = ['show_snackbar' => 'text', 'open_link' => 'link'];
    $type = array_key_exists(($res[1] ?? -1), $types) ? $res[1] : null;

    return $type === null ? $res : ['type' => $type, $types[$type] => $res[0]];
}

function messageEditOrSend(int $user_id, array $message, int $cmi): bool {
    $pars = [
        'peer_id' => $user_id,
        'message' => $message[0],
        'attachment' => $message[2] ?? null,
        'keyboard' => $message[1] ?? null,
    ];

    $req = VKHPM::make(
        $GLOBALS['config']->access_token,
        'messages.edit',
        $pars + ['conversation_message_id' => $cmi],
    );
    if ($req->ok === false) {
        $req = VKHPM::messagesSend($GLOBALS['config']->access_token, $pars);
    }

    return $req->ok ?? false;
}

# -- other
function strposArray(string $haystack, array $needle): bool|int {
    foreach ($needle as $what) {
        if (($pos = strpos($haystack, $what)) !== false) {
            return $pos;
        }
    }
    return false;
}

function dbConnect(): void {
    $config = require_once 'config/database.php';

    DB::setup(
        "mysql:host=$config->host;dbname=$config->database;charset=utf8",
        $config->user, $config->password
    );
}
