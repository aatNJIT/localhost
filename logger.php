<?php
//require_once('rabbitMQ/get_host_info.inc');
//require_once('rabbitMQ/rabbitMQLib.inc');
 require_once('rabbitMQ/RabbitClient.php');

function log_message($message)
{
    


    $client = RabbitClient::getConnection("Logging");
    $client->publish([
        'type' => 'log',
        'time' => date('c'),
        'host' => gethostname(),
        'msg'  => $message
    ]);
}

