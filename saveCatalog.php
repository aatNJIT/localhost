<?php
session_start();

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

$gameNames = $_POST['gameNames'];
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

$request = array();
$request['type'] = RequestType::SAVE_CATALOG;
$request[Identifiers::USER_ID] = $_SESSION[Identifiers::USER_ID];
$request['title'] = $title;
$request['ratings'] = $ratings;
$request['names'] = $gameNames;
RabbitClient::getConnection()->send_request($request);
header('Location: createCatalog.php?success=' . urlencode('Catalog saved'));
exit();