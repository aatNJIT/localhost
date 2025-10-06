<?php
session_start();

require_once('rabbitMQ/RabbitClient.php');

function checkSession() {
    if (!isset($_SESSION['userID']) || !isset($_SESSION['username']) || !isset($_SESSION['sessionID'])) {
        header('Location: ../login.php');
        exit();
    }

    $request = array(
        'type' => 'session',
        'sessionID' => $_SESSION['sessionID'],
        'userID' => $_SESSION['userID']
    );

    $client = RabbitClient::getConnection();
    $response = $client->send_request($request);

    if ($response === false) {
        session_destroy();
        echo "Expired Session";
        exit();
    }
}

checkSession();