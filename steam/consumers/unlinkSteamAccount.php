<?php
require_once('../../rabbitMQ/RabbitClient.php');
session_start();

if (isset($_SESSION['username']) && isset($_SESSION['userID'])) {
    unset($_SESSION['steamid']);
    $request = array();
    $request['type'] = 'unlink';
    $request['userID'] = $_SESSION['userID'];
    RabbitClient::getConnection()->send_request($request);
    header('Location: ../../profile.php');
} else {
    header('Location: ../../index.php');
}

exit();