<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once('../../rabbitMQ/RabbitClient.php');
session_start();

if (isset($_SESSION['username']) && isset($_SESSION['userID']) && isset($_SESSION['steamid'])) {
    $request = array();
    $request['type'] = 'link';
    $request['userID'] = $_SESSION['userID'];
    $request['steamID'] = $_SESSION['steamid'];
    if (RabbitClient::getConnection()->send_request($request)) {
        header('Location: ../../profile.php');
    } else {
        unset($_SESSION['steamid']);
        header('Location: ../../index.php');
    }
} else {
    header('Location: ../../index.php');
}

exit();