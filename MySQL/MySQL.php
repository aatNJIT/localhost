<?php

class MySQL
{
    public static $connection = null;

    static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = new mysqli('192.168.56.101', 'IT490', 'IT490', 'IT490');
        }
        return self::$connection;
    }

}
