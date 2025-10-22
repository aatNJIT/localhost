<?php
require_once('../../rabbitMQ/RabbitClient.php');
require_once('../../identifiers.php');
session_start();

if (isset($_SESSION[Identifiers::USER_ID]) && isset($_SESSION[Identifiers::STEAM_ID])) {
    $request = array();
    $request['type'] = RequestType::LINK;
    $request[Identifiers::USER_ID] = $_SESSION[Identifiers::USER_ID];
    $request[Identifiers::STEAM_ID] = $_SESSION[Identifiers::STEAM_ID];
    if (RabbitClient::getConnection()->send_request($request)) {
        header('Location: ../../profile.php');
    } else {
        unset($_SESSION[IDentifiers::STEAM_ID]);
        header('Location: ../../index.php');
    }
} else {
    header('Location: ../../index.php');
}

exit();