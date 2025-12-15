<?php

class MySQL
{
    public static ?mysqli $connection = null;

    static function getConnection(): ?mysqli
    {
        if (self::$connection == null) {
            self::$connection = new mysqli('127.0.0.1', 'it490', 'it490','it490');
        }
        return self::$connection;
    }

}
