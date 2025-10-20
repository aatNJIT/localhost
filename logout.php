<?php
session_start();
require_once('rabbitMQ/RabbitClient.php');

if (isset($_SESSION['sessionID']) && isset($_SESSION['userID']) && isset($_SESSION['username'])) {
    $request = array(
        'type' => 'logout',
        'sessionID' => $_SESSION['sessionID'],
        'userID' => $_SESSION['userID'],
    );
    $client = RabbitClient::getConnection();
    $client->send_request($request);
}

session_destroy();
session_regenerate_id();
header('Location: index.php');
exit();
