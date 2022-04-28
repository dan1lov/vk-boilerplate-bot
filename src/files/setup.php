<?php

date_default_timezone_set("Europe/Moscow");
$home = realpath(__DIR__ . "/../");

$config = require_once "$home/files/config/main.php";
$settings = require_once "$home/files/config/settings.php";

$config->access_token = $config->access_token_array[array_rand(
    $config->access_token_array
)];

require_once "$home/files/config/constants.php";
require_once "$home/files/functions.php";

// libs
require_once "$home/libs/Database.php";
require_once "$home/libs/VKHP_onefile.php";

// after-load actions
dbConnect();
