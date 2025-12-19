<?php
require_once('../../rabbitMQ/RabbitClient.php');
require_once('../../identifiers.php');
require_once('../../logger.php');
session_start();

if (isset($_SESSION[Identifiers::USER_ID])) {
    unset($_SESSION[Identifiers::STEAM_ID]);
    unset($_SESSION[Identifiers::STEAM_PROFILE]);
    $request = array();
    $request['type'] = RequestType::UNLINK;
    $request[Identifiers::USER_ID] = $_SESSION[Identifiers::USER_ID];
    RabbitClient::getConnection()->send_request($request);
    header('Location: ../../profile.php');
} else {
    log_message("User failed to unlink Steam account " . $_SESSION[Identifiers::USER_ID]);
    header('Location: ../../index.php');
}

exit();