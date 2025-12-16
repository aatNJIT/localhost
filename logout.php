<?php
require_once 'logger.php';
log_message("logout.php page loaded. Method=".$_SERVER['REQUEST_METHOD']);

session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])) {
    $request = array(
        'type' => REQUESTType::LOGOUT,
        Identifiers::SESSION_ID => $_SESSION[Identifiers::SESSION_ID],
        Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID],
        log_message("Logging out user " . $_SESSION[Identifiers::USERNAME])
    );
    $client = RabbitClient::getConnection();
    $client->send_request($request);
    log_message("User " . $_SESSION[Identifiers::USERNAME] . " logged out successfully.");
}

session_destroy();
log_message("Redirecting to index.php page.");
header('Location: index.php');


exit();
