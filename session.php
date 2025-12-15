<?php
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

function checkSession(): void
{
    $isLoggedIn = isset($_SESSION[Identifiers::USER_ID]) && isset($_SESSION[Identifiers::SESSION_ID]);

    if (!$isLoggedIn) {
        header('Location: login.php');
        exit();
    }

    $request = array(
        'type' => RequestType::SESSION,
        Identifiers::SESSION_ID => $_SESSION[Identifiers::SESSION_ID],
        Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]
    );

    $response = RabbitClient::getConnection()->send_request($request);

    if (!$response) {
        header('Location: logout.php');
        exit();
    }
}

checkSession();