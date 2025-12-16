#!/usr/bin/php
<?php
require_once('../MySQL/MySQL.php');
require_once('../RabbitMQ/path.inc');
require_once('../RabbitMQ/get_host_info.inc');
require_once('../RabbitMQ/rabbitMQLib.inc');

$connection = MySQL::getConnection();

if ($connection->connect_errno != 0) {
    echo "Failed to connect to MySQL database " . $connection->connect_error . " " . PHP_EOL;
    exit();
}

$steamApi = new rabbitMQClient("rabbitMQ.ini", "SteamAPI");

$lastAppID = 0;
$insertStatement = $connection->prepare("INSERT IGNORE INTO Games (AppID, Name) VALUES (?, ?)");

if (!$insertStatement) {
    echo "Invalid SQL statement" . PHP_EOL;
    exit();
}

$totalGames = 0;
$iteration = 0;

do {
    $iteration++;
    echo "Iteration #$iteration" . PHP_EOL;

    $response = (array)$steamApi->send_request([
            'type' => 'getallgames',
            'lastappid' => $lastAppID
    ]);

    if (empty($response)) {
        echo "Invalid response or no more games" . PHP_EOL;
        break;
    }

    $gameCount = count($response);
    $inserted = 0;

    foreach ($response as $app) {
        $appID = $app['appid'];
        $name = $app['name'];

        $insertStatement->bind_param("is", $appID, $name);
        if ($insertStatement->execute()) {
            if ($insertStatement->affected_rows > 0) {
                $inserted++;
            }
        }

        $lastAppID = $appID;
    }

    $totalGames += $inserted;
    echo "Iteration #$iteration: Received $gameCount games, Inserted $inserted new games, Last AppID: $lastAppID, Total: $totalGames" . PHP_EOL;

    if ($gameCount < 50000) {
        break;
    }
} while (true);

echo "Total Inserted: $totalGames" . PHP_EOL;
exit();