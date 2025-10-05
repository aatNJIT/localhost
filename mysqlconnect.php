#!/usr/bin/php
<?php

mysqli_report(MYSQLI_REPORT_OFF); 

$mydb = new mysqli('192.168.56.105','it490','it490','it490');

if ($mydb->errno != 0)
{
	echo "failed to connect to database: ". $mydb->error . PHP_EOL;
	exit(0);
}

echo "successfully connected to Users".PHP_EOL;

$query = "select * from Users;";

$response = $mydb->query($query);
if ($mydb->errno != 0)
{
	echo "failed to execute query:".PHP_EOL;
	echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
	exit(0);
}
// User test variables
$test_username = "testuser";
$test_password = "password";
$it490db = "INSERT INTO Users (Username, Password) VALUES (?, ?)";

$stmt = $mydb->prepare($it490db);

if ($stmt !== false) {
    $stmt->bind_param("ss", $test_username, $test_password); // PARAMETERS SENT AS STRING

	if ($stmt->execute()) { // SUCCESS RESPONSE FROM IT490 DB
		echo "Success the user $test_username was inserted." .PHP_EOL;
	}
	else { // FAILURE RESPONSE FROM IT490 DB
		echo "The user $test_username failed to insert." .PHP_EOL;
		echo "Error: ". $stmt->error .PHP_EOL;
	}

    $stmt->close();
}

$mydb->close();

?>

