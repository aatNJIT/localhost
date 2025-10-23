<?php
$path = '/usr/local/lib/system_libs';

if (file_exists('rabbitMQ/local.ini')) {
    $parse = parse_ini_file('rabbitMQ/local.ini', true);
    if (isset($parse['paths'])) {
        $path = $parse['paths']['libraries'];
    } else {
        trigger_error("local.ini missing paths field\n");
    }
}

set_include_path(get_include_path() . PATH_SEPARATOR . $path);