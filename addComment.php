<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');
require_once('logger.php');

$comment = $_POST['comment'];
$catalogID = $_POST['catalogid'];

if (!isset($catalogID) || !isset($comment) || !isset($_SESSION[Identifiers::USER_ID])) {
    header('Location: viewCatalog.php?catalogid=' . $catalogID . '&error=' . urlencode('Missing required fields'));
    exit();
}

if (strlen($comment) < 1 || strlen($comment) > 255) {
    header('Location: viewCatalog.php?catalogid=' . $catalogID . '&error=' . urlencode('Invalid comment'));
    exit();
}

$request = array(
    'type' => RequestType::ADD_CATALOG_COMMENT,
    Identifiers::CATALOG_ID => $catalogID,
    Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID],
    'comment' => $comment
);

$response = RabbitClient::getConnection()->send_request($request);

if ($response) {
    header('Location: viewCatalog.php?catalogid=' . $catalogID . '&success=' . urlencode('Added comment'));
    exit();
}

log_message("Failed to add comment for catalog $catalogID");
header('Location: viewCatalog.php?catalogid=' . $catalogID . '&error=' . urlencode('Error adding comment'));
exit();