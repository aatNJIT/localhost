<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

$title = $_POST["title"];
$ratings = $_POST["ratings"];

if (!isset($title) || !isset($ratings)) {
    header('Location: createCatalog.php?error=' . urlencode('Invalid ratings or invalid title'));
    exit();
}

if (empty($ratings)) {
    header('Location: createCatalog.php?error=' . urlencode('Invalid ratings'));
    exit();
}

if (strlen($title) < 1 || strlen($title) > 255) {
    header('Location: createCatalog.php?error=' . urlencode('Invalid title'));
    exit();
}

foreach ($ratings as $id => $rating) {
    if ($rating < 1 || $rating > 10) {
        header('Location: createCatalog.php?error=' . urlencode('Invalid rating'));
        exit();
    }
}

RabbitClient::getConnection()->send_request([
    'type' => RequestType::SAVE_CATALOG,
    Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID],
    'title' => $title,
    'ratings' => $ratings]
);

header('Location: createCatalog.php?success=' . urlencode('Catalog saved'));
exit();