<?php

class MySQL
{
    public static $connection = null;

    static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = new mysqli('100.86.244.3', 'it490', 'YourSecurePassword','it490');
        }
        return self::$connection;
    }

}
