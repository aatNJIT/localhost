<?php
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

$userIDToFollow = $_POST[Identifiers::USER_ID];
$username = $_POST[Identifiers::USERNAME];

if (!isset($userIDToFollow) || !isset($username)) {
    header("Location: users.php?error=" . urlencode("Invalid user id or username"));
    exit();
}

$followerUserID = $_SESSION[Identifiers::USER_ID];
header("Location: users.php?error=" . urlencode($followerUserID));

if ($userIDToFollow == $followerUserID) {
    header("Location: users.php?error=" . urlencode("Cannot follow yourself"));
    exit();
}

session_start();

$request = array(
    'type' => RequestType::FOLLOW_USER,
    Identifiers::USER_ID => $followerUserID,
    'followid' => $userIDToFollow,
);

$response = RabbitClient::getConnection()->send_request($request);

if (!$response) {
    header('Location: users.php?success=' . urlencode('Followed User: ' . $username));
    exit();
}

header("Location: users.php?error=" . urlencode("Error following user: " . $username));
exit();

