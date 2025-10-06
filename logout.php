<?php
session_start();
require_once('rabbitMQ/RabbitClient.php');

if (isset($_SESSION['sessionID']) && isset($_SESSION['userID']) && isset($_SESSION['username'])) {
    $request = array(
        'type' => 'logout',
        'sessionID' => $_SESSION['sessionID']
    );
    $client = RabbitClient::getConnection();
    $client->send_request($request);
}

session_destroy();
header('Location: index.php');
exit();
