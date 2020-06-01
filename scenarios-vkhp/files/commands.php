<?php
use \VKHP\Generator as VKHPG;
use \VKHP\Method as VKHPM;
use \VKHP\Scenarios as VKHPTemp;

return [
    'help' => [
        'aliases' => [ '/меню', 'меню', '/help' ],
        'exec' => function ($object, $config, $user_id) {
            $answer = "Привет, пользователь! Вот список моих команд:";
            $keyboard = VKHPG::keyboard([[
                VKHPG::button('Установить никнейм', VKHPG::WHITE),
                VKHPG::button('Узнать мой ник', VKHPG::WHITE),
            ], [
                VKHPG::button('Удалить мой ник', VKHPG::GREEN),
            ]]);

            return [ $answer, $keyboard ];
        }
    ],

    'set_nickname' => [
        'aliases' => [ 'установить никнейм' ],
        'exec' => function ($object, $config, $user_id) {
            global $temp, $home;

            switch ($temp->stage ?? null) {
                case 'nickname':
                    $temp->stage = 'confirm';
                    $temp->new_nickname = $object->message->text;
                    $temp->save();

                    $answer = "Подвердите установку своего нового ника «{$temp->new_nickname}»";
                    $keyboard = VKHPG::keyboard([[
                        VKHPG::button('Подтверждаю', VKHPG::GREEN),
                        VKHPG::button('Отменить',    VKHPG::BLUE),
                    ]]);
                    break;
                case 'confirm':
                    global $message;
                    $temp->clear();

                    $keyboard = VKHPG::keyboard([[ VKHPG::button('/меню', VKHPG::BLUE) ]]);
                    if ($message === 'отменить') {
                        return [ 'Вы отменили установку нового ника.', $keyboard ];
                    }

                    $data_path = "{$home}/files/data/user_{$user_id}.json";
                    $contents  = file_exists($data_path) ? json_decode(file_get_contents( $data_path ), true) : [];

                    $contents['nickname'] = $temp->new_nickname;
                    file_put_contents($data_path, json_encode( $contents ));

                    $answer = 'Новый ник успешно сохранен!';
                    break;
                default:
                    $temp = new VKHPTemp("{$home}/files/temp/scenarios", $user_id);
                    $temp->command = 'set_nickname';
                    $temp->stage = 'nickname';
                    $temp->save();

                    $answer = 'Введите ваш новый никнейм:';
                    $keyboard = VKHPG::keyboard([], VKHPG::KM_ONETIME);
            }
            return [ $answer, $keyboard ];
        }
    ],

    'show_nickname' => [
        'aliases' => [ 'узнать мой ник' ],
        'exec' => function ($object, $config, $user_id) {
            global $home;

            $data_path = "{$home}/files/data/user_{$user_id}.json";
            if (! file_exists($data_path)) {
                return [ 'Вы ещё не устанавливали себе никнейм.' ];
            }

            $contents = json_decode(file_get_contents( $data_path ), true);
            return [ "Ваш никнейм: {$contents['nickname']}" ];
        }
    ],

    'delete_nickname' => [
        'aliases' => [ 'удалить мой ник' ],
        'exec' => function ($object, $config, $user_id) {
            global $home;

            $data_path = "{$home}/files/data/user_{$user_id}.json";
            if (file_exists($data_path)) {
                unlink($data_path);
            }

            return [ "Ваш никнейм был успешно удален." ];
        }
    ],
];
