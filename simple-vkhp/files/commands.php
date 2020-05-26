<?php
use \VKHP\Generator as VKHPG;
use \VKHP\Method as VKHPM;

return [
    [
        'aliases' => [ '/меню', 'меню', '/help' ],
        'exec' => function ($object, $config, $user_id) {
            $user_info = VKHPM::make($config['access_token'], 'users.get', [
                'user_ids' => $user_id,
            ]);
            $first_name = $user_info->ok ? $user_info->response[0]->first_name : 'Пользователь';

            $answer = "{$first_name}, нажимай кнопку, чтобы вызвать другую команду.";
            $buttons = [[
                VKHPG::button('Курс Валют', VKHPG::WHITE),
                VKHPG::button('Погода', VKHPG::WHITE),
            ], [
                VKHPG::button('Покажи котика', VKHPG::GREEN),
            ]];

            return [ $answer, VKHPG::keyboard($buttons) ];
        }
    ],
    [
        'aliases' => [ 'курс валют' ],
        'exec' => function ($object, $config, $user_id) {
            $cbr_daily = json_decode(file_get_contents( 'https://www.cbr-xml-daily.ru/daily_json.js' ));

            $template = "&#8986; {time}\n&#128181; {usd_nominal} USD = {usd2rub_value} руб.\n&#128183; {eur_nominal} EUR = {eur2rub_value} руб.";
            $answer = strtr($template, [
                '{time}' => date('d.m.Y, H:i', strtotime($cbr_daily->Date)),
                '{usd_nominal}' => $cbr_daily->Valute->USD->Nominal,
                '{usd2rub_value}' => $cbr_daily->Valute->USD->Value,
                '{eur_nominal}' => $cbr_daily->Valute->EUR->Nominal,
                '{eur2rub_value}' => $cbr_daily->Valute->EUR->Value
            ]);

            return [ $answer ];
        }
    ],
    [
        'aliases' => [ 'погода' ],
        'exec' => function ($object, $config, $user_id) {
            global $m_parts;
            if (! isset($m_parts[1])) {
                return [ '&#10006; Необходимо указать город, погоду в котором вы хотите узнать. Например, «погода москва»' ];
            }

            $city = implode(' ', array_slice($m_parts, 1));
            $apikey = 'you_api_key';
            $api_url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&lang=ru&units=metric&appid={$apikey}";
            $weather = json_decode(file_get_contents( $api_url ));
            if (! isset($weather->cod) || $weather->cod !== 200) {
                return [ '&#10006; Город не найден.' ];
            }

            $template = '{desc}, {temp} °C';
            $answer = strtr($template, [
                '{desc}' => $weather->weather[0]->description,
                '{temp}' => $weather->main->temp,
            ]);

            return [ $answer ];
        }
    ],
    [
        'aliases' => [ 'покажи котика' ],
        'exec' => function ($object, $config, $user_id) {
            global $peer_id;
            $images = [
                'https://cdn.pixabay.com/photo/2014/11/30/14/11/kitty-551554_960_720.jpg',
                'https://cdn.pixabay.com/photo/2017/02/20/18/03/cat-2083492_960_720.jpg',
                'https://cdn.pixabay.com/photo/2014/04/13/20/49/cat-323262_960_720.jpg'
            ];

            $answer = 'Вот, держи :3';
            $attachment = VKHPM::uploadMessagesPhoto($config['access_token'], [ $images[array_rand($images)] ], [ 'peer_id' => $peer_id ]);

            return [ $answer, null, $attachment[0] ];
        }
    ],
];
