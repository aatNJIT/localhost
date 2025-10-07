#!/usr/bin/php
<?php
mysqli_report(MYSQLI_REPORT_OFF);

$mydb = new mysqli('192.168.56.105','it490','it490','it490');

if ($mydb->connect_errno) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(1);
}

echo "Successfully connected to database" . PHP_EOL;
