<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])) {
    $request = array(
        'type' => REQUESTType::LOGOUT,
        Identifiers::SESSION_ID => $_SESSION[Identifiers::SESSION_ID],
        Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID],
    );
    $client = RabbitClient::getConnection();
    $client->send_request($request);
}

session_destroy();
header('Location: index.php');
exit();
