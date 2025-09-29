<?php

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