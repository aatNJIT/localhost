<?php
session_start();
require_once('steam/SteamUtils.php');
require_once('session.php');
require_once('identifiers.php');

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$client = RabbitClient::getConnection();

if (!isset($_GET['action'])) {
    echo json_encode([]);
    exit();
}

$action = $_GET['action'];

if ($action === 'search') {
    $name = $_GET['name'] ?? '';
    $games = (array)$client->send_request([
        'type' => RequestType::SEARCH_GAMES,
        'name' => $name,
        'limit' => PHP_INT_MAX
    ]);
    echo json_encode($games);
    exit();
}

if ($action === 'games') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    $games = (array)$client->send_request([
        'type' => RequestType::GAMES,
        'offset' => $offset,
        'limit' => $limit
    ]);
    echo json_encode($games);
    exit();
}

echo json_encode([]);
exit();