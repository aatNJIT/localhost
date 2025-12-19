#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$server = new rabbitMQServer("rabbitMQ.ini", "Logging");

echo "LOGGER WORKER STARTED\n";

$server->process_requests('requestProcessor');

function requestProcessor($request)
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    if ($request['type'] == 'log') {
        return writeLog($request);
    }

    return false;
}

function writeLog($request)
{
    $line = sprintf(
        "[%s] [%s] %s\n",
        $request['time'] ?? date('c'),
        $request['host'] ?? 'unknown',
        $request['msg']  ?? 'no message'
    );

    file_put_contents(
        "/var/log/it490/system.log",
        $line,
        FILE_APPEND
    );

    return true;
}

