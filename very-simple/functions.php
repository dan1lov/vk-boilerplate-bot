<?php

function api($method, $params) {
    $url = "https://api.vk.com/method/{$method}?" . http_build_query($params);
    return json_decode(file_get_contents( $url ));
}
