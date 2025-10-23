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
        $request = ['type' => RequestType::PROFILE, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
        $response = RabbitClient::getConnection("SteamAPI")->send_request($request);
        $_SESSION[Identifiers::STEAM_PROFILE] = $response;
        $_SESSION[Identifiers::LAST_GAME_SESSION_CHECK] = 0;
        header('Location: ../../profile.php');
    } else {
        unset($_SESSION[IDentifiers::STEAM_ID]);
        header('Location: ../../index.php');
    }
} else {
    header('Location: ../../index.php');
}

exit();