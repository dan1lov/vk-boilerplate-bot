<?php

date_default_timezone_set('Europe/Moscow');
$home = realpath(__DIR__ . '/../');

# -- general
$config = require_once "$home/files/config/main.php";
$settings = require_once "$home/files/config/settings.php";
$templates = require_once "$home/files/config/templates.php";

$random_index = array_rand($config->access_token_array);
$config->access_token = $config->access_token_array[$random_index];
unset($random_index);

require_once "$home/files/config/constants.php";
require_once "$home/files/functions.php";

# -- libs
require_once "$home/libs/Database.php";
require_once "$home/libs/VKHP_onefile.php";

# -- after-load actions
databaseConnect();
