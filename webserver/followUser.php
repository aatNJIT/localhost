<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

if (!isset($_SESSION[Identifiers::USER_ID])) {
    header("Location: users.php?error=" . urlencode("You must be logged in to follow users"));
    exit();
}

$userIDToFollow = $_POST['userid'] ?? null;
$username = $_POST['username'] ?? null;

if (!$userIDToFollow || !$username) {
    header("Location: users.php?error=" . urlencode("Invalid user id or username"));
    exit();
}

$followerUserID = $_SESSION[Identifiers::USER_ID];

if ($userIDToFollow == $followerUserID) {
    header("Location: users.php?error=" . urlencode("Cannot follow yourself"));
    exit();
}

$request = array(
    'type' => RequestType::FOLLOW_USER,
    Identifiers::USER_ID => $followerUserID,
    Identifiers::FOLLOW_ID => $userIDToFollow,
);

$response = RabbitClient::getConnection()->send_request($request);

if ($response) {
    header('Location: users.php?success=' . urlencode('Followed User: ' . $username));
    exit();
}

header("Location: users.php?error=" . urlencode("Error following user: " . $username));
exit();