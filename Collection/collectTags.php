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
$selectStatement = $connection->prepare("SELECT AppID FROM Games WHERE Tags IS NULL OR Description IS NULL LIMIT 200");

if (!$selectStatement) {
    echo "Invalid SQL SELECT statement" . PHP_EOL;
    exit();
}

$updateStatement = $connection->prepare("UPDATE Games SET Tags = ?, Description = ? WHERE AppID = ?");

if (!$updateStatement) {
    echo "Invalid SQL UPDATE statement" . PHP_EOL;
    exit();
}

$selectStatement->execute();
$result = $selectStatement->get_result();

echo "Starting to collect tags and descriptions..." . PHP_EOL;

while ($row = $result->fetch_assoc()) {
    $appID = $row['AppID'];

    $response = (array)$steamApi->send_request([
            'type' => 'getgametagsanddesscriptions',
            'appid' => $appID
    ]);

    if (!empty($response) && isset($response['tags'])) {
        $tags = json_encode($response['tags']);
        $description = $response['description'] ?? '';
        $updateStatement->bind_param("ssi", $tags, $description, $appID);
        if ($updateStatement->execute() && $updateStatement->affected_rows > 0) {
            echo "Updated AppID $appID: " . count($response['tags']) . " tags collected" . PHP_EOL;
        }
    } else {
        echo "Failed to get data for AppID $appID" . PHP_EOL;
    }

    usleep(1600000);
}
exit();