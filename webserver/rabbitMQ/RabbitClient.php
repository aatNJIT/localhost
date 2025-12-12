<?php

require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

class RabbitClient
{
    private static array $connections = [];

    static function getConnection(string $server = "Default"): rabbitMQClient
    {
        if (!isset(self::$connections[$server])) {
            self::$connections[$server] = new rabbitMQClient("rabbitMQ.ini", $server);
        }
        return self::$connections[$server];
    }
}