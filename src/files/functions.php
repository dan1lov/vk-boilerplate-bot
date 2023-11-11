<?php

use Danilov\Database as DB;
use VKHP\Generator as VKHPG;
use VKHP\Method as VKHPM;

# -- user
function userGetOrCreate(int $user_id): object {
    if ($user_id <= 0) {
        die('ok');
    }

    $sql = 'SELECT * FROM users WHERE user_id = ?';
    $user = DB::getRow($sql, [$user_id]);

    if (empty($user)) {
        DB::execute('INSERT INTO users (user_id) VALUES (?)', [$user_id]);
        $user = DB::getRow($sql, [$user_id]);
    }

    return (object)$user;
}

function getUserField(string $field, int $user_id) {
    return DB::getOne('SELECT $field FROM users WHERE user_id = ?', [$user_id]);
}

function changeUserField(string $field, int $user_id, $val): void {
    $fs = is_int($val) ? "$field = $field + ?" : "$field = ?";
    DB::execute("UPDATE users SET $fs WHERE user_id = ?", [$val, $user_id]);
}

# -- events
function validateMessageArray(array $message): array {
    if (isAssoc($message)) {
        return $message;
    }

    return [
        'message' => $message[0] ?? getTemplate('default.undefined'),
        'keyboard' => $message[1] ?? null,
        'attachment' => $message[2] ?? null,
        'template' => $message[3] ?? null,
    ];
}

function processingMessageEvent(object $object, array $event_data): bool {
    global $config;

    if (!isset($event_data['type'])) {
        return messageEditOrSend(
            $object->user_id,
            $event_data,
            $object->conversation_message_id,
        );
    }

    return VKHPM::make(
        $config->access_token,
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

function sendMessageObjectTo(mixed $user_ids, array $message): object {
    global $config;

    return VKHPM::messagesSend(
        $config->access_token,
        validateMessageArray($message) + [
            'user_ids' => $user_ids,
            'dont_parse_links' => true,
            'disable_mentions' => true,
        ],
    );
}

function messageEditOrSend(int $user_id, array $message, int $cmi): int {
    global $config;
    $message = validateMessageArray($message);

    $edit = VKHPM::make(
        $config->access_token,
        'messages.edit',
        $message + [
            'peer_id' => $user_id,
            'conversation_message_id' => $cmi,
            'dont_parse_links' => true,
        ],
    );

    if ($edit->ok === false) {
        $send = sendMessageObjectTo($user_id, $message);
    }

    return $send->response[0]->conversation_message_id ?? ($edit->ok ? $cmi : 0);
}

# -- other
function isAssoc(array $array): bool {
    if (empty($array)) {
        return false;
    }

    return array_keys($array) !== range(0, count($array) - 1);
}

function getTemplate(string $name, mixed ...$values): string {
    global $templates;
    return sprintf($templates[$name] ?? 'undefined', ...$values);
}

function strposArray(string $haystack, array $needle): bool|int {
    foreach ($needle as $what) {
        if (($pos = strpos($haystack, $what)) !== false) {
            return $pos;
        }
    }
    return false;
}

function databaseConnect(): void {
    $config = require_once 'config/database.php';

    DB::setup(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config->host,
            $config->database,
        ),
        $config->user,
        $config->password,
    );
}

# -- keyboard
function getKeyboardMenuDefault(): string {
    return VKHPG::keyboard([[
        VKHPG::buttonCallback(
            getTemplate('button.snackbar'),
            VKHPG::WHITE,
            ['c' => 'snack'],
        ),
        VKHPG::buttonCallback(
            getTemplate('button.next-step'),
            VKHPG::WHITE,
            ['c' => 'step'],
        ),
    ], [
        VKHPG::buttonCallback(
            getTemplate('button.sign-up'),
            VKHPG::BLUE,
            ['c' => 'signup'],
        ),
    ]], VKHPG::KM_INLINE);
}

function getKeyboardStepDefault(): string {
    return VKHPG::keyboard([[
        VKHPG::buttonCallback(
            getTemplate('button.open-link'),
            VKHPG::WHITE,
            ['c' => 'link'],
        ),
    ], [
        VKHPG::buttonCallback(
            getTemplate('button.back'),
            VKHPG::BLUE,
            ['c' => 'menu'],
        ),
    ]], VKHPG::KM_INLINE);
}

function getKeyboardSignupBack(): string {
    return VKHPG::keyboard([[
        VKHPG::buttonCallback(
            getTemplate('button.back-menu'),
            VKHPG::BLUE,
            ['c' => 'menu'],
        ),
    ]], VKHPG::KM_INLINE);
}

function getKeyboardSignupUsername(): string {
    return VKHPG::keyboard([[
        VKHPG::buttonCallback(
            getTemplate('button.yes'),
            VKHPG::BLUE,
            ['a' => 'confirm'],
        ),
        VKHPG::buttonCallback(
            getTemplate('button.another'),
        ),
    ], [
        VKHPG::buttonCallback(
            getTemplate('button.cancel'),
            VKHPG::RED,
            ['a' => 'dismiss'],
        ),
    ]], VKHPG::KM_INLINE);
}
