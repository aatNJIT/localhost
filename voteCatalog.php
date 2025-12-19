<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

if (!isset($_SESSION[Identifiers::USER_ID]) || !isset($_GET['catalogid']) || !is_numeric($_GET['catalogid'])) {
    header("Location: " . $_SERVER['HTTP_REFERER'] ?? "index.php");
    exit();
}

$userID = $_SESSION[Identifiers::USER_ID];
$catalogID = $_GET['catalogid'];

if (!isset($_GET['action'])) {
    header("Location: " . $_SERVER['HTTP_REFERER'] ?? "index.php");
    exit();
}

$action = strtolower($_GET['action']);
if ($action !== 'up' && $action !== 'down') {
    header("Location: " . $_SERVER['HTTP_REFERER'] ?? "index.php");
    exit();
}

RabbitClient::getConnection()->send_request([
    'type' => RequestType::VOTE_CATALOG,
    Identifiers::USER_ID => $userID,
    Identifiers::CATALOG_ID => $catalogID,
    'action' => $action
]);

header("Location: " . $_SERVER['HTTP_REFERER'] ?? "index.php");