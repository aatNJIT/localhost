<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function log_message($message)
{
    $client = new rabbitMQClient("rabbitMQ.ini", "Logging");
    $client->publish([
        'type' => 'log',
        'time' => date('c'),
        'host' => gethostname(),
        'msg'  => $message
    ]);
}

