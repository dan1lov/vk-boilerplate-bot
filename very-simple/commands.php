<?php

if ($message === '/меню') {
    $user_info = api('users.get', [
        'user_ids' => $user_id,
        'access_token' => $config['access_token'],
        'v' => $config['v'],
    ]);
    $first_name = $user_info->response[0]->first_name ?? 'Пользователь';

    $answer = "{$first_name}, привет! Вот мои команды:";
    $keyboard = json_encode([
        'one_time' => false,
        'buttons' => [[
            [ 'action' => [ 'type' => 'text', 'label' => 'Курс валют', 'payload' => '{"button":1}' ], 'color' => 'secondary' ],
            [ 'action' => [ 'type' => 'text', 'label' => 'Погода', 'payload' => '{"button":2}' ], 'color' => 'secondary' ],
        ]],
    ]);
}

elseif ($message === 'курс валют') {
    $cbr_daily = json_decode(file_get_contents( 'https://www.cbr-xml-daily.ru/daily_json.js' ));

    $template = "&#8986; {time}\n&#128181; {usd_nominal} USD = {usd2rub_value} руб.\n&#128183; {eur_nominal} EUR = {eur2rub_value} руб.";
    $answer = strtr($template, [
        '{time}' => date('d.m.Y, H:i', strtotime($cbr_daily->Date)),
        '{usd_nominal}' => $cbr_daily->Valute->USD->Nominal,
        '{usd2rub_value}' => $cbr_daily->Valute->USD->Value,
        '{eur_nominal}' => $cbr_daily->Valute->EUR->Nominal,
        '{eur2rub_value}' => $cbr_daily->Valute->EUR->Value
    ]);
}

elseif ($m_parts[0] === 'погода') {
    if (! isset($m_parts[1])) {
        $answer = '&#10006; Необходимо указать город, погоду в котором вы хотите узнать. Например, «погода москва»';
    } else {
        $city = implode(' ', array_slice($m_parts, 1));
        $apikey = 'you_api_key';
        $api_url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&lang=ru&units=metric&appid={$apikey}";
        $weather = json_decode(file_get_contents( $api_url ));
        if (! isset($weather->cod) || $weather->cod !== 200) {
            $answer = '&#10006; Город не найден.';
        } else {
            $template = '{desc}, {temp} °C';
            $answer = strtr($template, [
                '{desc}' => $weather->weather[0]->description,
                '{temp}' => $weather->main->temp,
            ]);
        }
    }
}
