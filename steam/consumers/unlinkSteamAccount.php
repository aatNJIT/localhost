<?php
require_once('../../rabbitMQ/RabbitClient.php');
require_once('../../identifiers.php');
session_start();

if (isset($_SESSION[Identifiers::USER_ID])) {
    unset($_SESSION[Identifiers::LAST_GAME_SESSION_CHECK]);
    unset($_SESSION[Identifiers::STEAM_ID]);
    unset($_SESSION[Identifiers::STEAM_PROFILE]);
    $request = array();
    $request['type'] = RequestType::UNLINK;
    $request[Identifiers::USER_ID] = $_SESSION[Identifiers::USER_ID];
    RabbitClient::getConnection()->send_request($request);
    header('Location: ../../profile.php');
} else {
    header('Location: ../../index.php');
}

exit();