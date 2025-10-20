<?php
session_start();

require_once('rabbitMQ/RabbitClient.php');

function checkSession()
{
    $isLoggedIn = (isset($_SESSION['userID']) && isset($_SESSION['sessionID']));

    if (!$isLoggedIn) {
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

    if (!$response) {
        session_destroy();
        session_regenerate_id();
        echo "Expired Session";
        exit();
    }
}

checkSession();