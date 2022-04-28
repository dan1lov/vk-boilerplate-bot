<?php

use VKHP\Generator as VKHPG;

return [
    'menu' => [
        'aliases' => [],
        'forAdmin' => false,
        'execute' => function () {
            global $user;

            $username = $user->username ?? 'noname';
            $usertag = "@id$user->user_id ($username)";

            $message = "Hello, $usertag! \u{1F44B}\nThat's my functions:";
            $keyboard = VKHPG::keyboard([[
                VKHPG::buttonCallback('snackbar', VKHPG::WHITE, ['c' => 'snack']),
                VKHPG::buttonCallback('next step', VKHPG::WHITE, ['c' => 'step']),
            ], [
                VKHPG::buttonCallback('sign up', VKHPG::BLUE, ['c' => 'signup']),
            ]], VKHPG::KM_INLINE);

            return [$message, $keyboard];
        }],

    'snack' => [
        'aliases' => [],
        'forAdmin' => false,
        'execute' => function () {
            return ["This is snackbar! \u{1F36B}", 'show_snackbar'];
        }],

    'link' => [
        'aliases' => [],
        'forAdmin' => false,
        'execute' => function () {
            return ['https://vk.com/ffwturtle', 'open_link'];
        }],

    'step' => [
        'aliases' => [],
        'forAdmin' => false,
        'execute' => function () {
            return [
                "\u{1F47E} second page:",
                VKHPG::keyboard([[
                    VKHPG::buttonCallback('open link', VKHPG::WHITE, ['c' => 'link']),
                    VKHPG::buttonCallback('back', VKHPG::BLUE, ['c' => 'menu']),
                ]], VKHPG::KM_INLINE)
            ];
        }],

    'signup' => [
        'aliases' => ['signup', 'sign up'],
        'forAdmin' => false,
        'execute' => function () {
            global $message, $payload, $settings, $temp, $user;

            $action = $temp->a ?? $payload->a ?? null;
            $back_keyboard = VKHPG::keyboard([[
                VKHPG::buttonCallback('back to menu', VKHPG::BLUE, ['c' => 'menu']),
            ]], VKHPG::KM_INLINE);

            switch ($action) {
                case 'username':
                    $username = $message->text ?? 'undefined';
                    if (empty($username)) {
                        $temp->save();
                        return ['your username cannot be empty, try again!'];
                    }

                    unset($temp->a);
                    $temp->username = $username;
                    $temp->save();

                    $message = "your username is «{$username}»?";
                    $keyboard = VKHPG::keyboard([[
                        VKHPG::buttonCallback('yes', VKHPG::BLUE, ['a' => 'confirm']),
                        VKHPG::buttonCallback('another', VKHPG::WHITE),
                    ], [
                        VKHPG::buttonCallback('cancel', VKHPG::RED, ['a' => 'dismiss']),
                    ]], VKHPG::KM_INLINE);
                    break;

                case 'confirm':
                    $temp->clear();
                    changeUserField('username', $user->user_id, $temp->username);

                    $message = "successfully signup!";
                    $keyboard = $back_keyboard;
                    break;
                case 'dismiss':
                    $temp->clear();

                    $message = "signup cancelled!";
                    $keyboard = $back_keyboard;
                    break;

                default:
                    $temp = new VKHP\Scenarios($settings->tsf, $user->user_id);
                    $temp->__onetime = true;
                    $temp->c = 'signup';
                    $temp->a = 'username';
                    $temp->save();

                    $message = "please, enter your username with next message.";
                    $keyboard = VKHPG::keyboard([], VKHPG::KM_ONETIME);
                    break;
            }
            return [$message, $keyboard];
        }],
];
