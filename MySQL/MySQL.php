<?php

class MySQL
{
    public static $connection = null;

    static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = new mysqli('100.95.11.48', 'it490', 'it490', 'it490');
        }
        return self::$connection;
    }

}
