<?php
 require_once('rabbitMQ/RabbitClient.php');

function log_message($message): void
{
    $client = RabbitClient::getConnection("Logging");
    $client->publish([
        'type' => 'log',
        'time' => date('c'),
        'host' => gethostname(),
        'msg'  => $message
    ]);
}

