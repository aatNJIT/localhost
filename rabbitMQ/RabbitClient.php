<?php

require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

class RabbitClient
{
    public static $connection = null;

    static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = new rabbitMQClient("testRabbitMQ.ini", "testServer");
        }
        return self::$connection;
    }

}