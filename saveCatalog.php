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

$request = ['type' => RequestType::GET_USER_GAMES, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]];
$userGames = RabbitClient::getConnection()->send_request($request);

if (!is_array($userGames)) {
    header('Location: createCatalog.php?error=' . urlencode('Failed to validate games'));
    exit();
}

$userGameNamesAndIds = array_column($userGames, 'Name', 'AppID');
$userGameTags = array_column($userGames, 'Tags', 'AppID');
$processedRatings = [];
$processedNames = [];

foreach ($ratings as $appId => $rating) {
    if (!isset($userGameNamesAndIds[$appId])) {
        header('Location: createCatalog.php?error=' . urlencode('Invalid game selected'));
        exit();
    }

    $processedRatings[$appId] = $rating;
    $processedNames[$appId] = $userGameNamesAndIds[$appId];
    $existingTags = isset($userGameTags[$appId]) ? json_decode($userGameTags[$appId], true) : [];

    if (empty($existingTags)) {
        $tagsRequest = [
            'type' => RequestType::GET_TAGS,
            'appid' => $appId
        ];

        $tagsResponse = RabbitClient::getConnection("SteamAPI")->send_request($tagsRequest);

        if (is_array($tagsResponse) && !empty($tagsResponse)) {
            $storeRequest = [
                'type' => RequestType::STORE_GAME_TAGS,
                'appid' => $appId,
                'tags' => $tagsResponse,
            ];
            RabbitClient::getConnection()->send_request($storeRequest);
        }
    }
}

$request = array();
$request['type'] = RequestType::SAVE_CATALOG;
$request[Identifiers::USER_ID] = $_SESSION[Identifiers::USER_ID];
$request['title'] = $title;
$request['ratings'] = $processedRatings;
$request['names'] = $processedNames;
RabbitClient::getConnection()->send_request($request);
header('Location: createCatalog.php?success=' . urlencode('Catalog saved'));
exit();