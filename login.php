<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('rabbitMQ/path.inc');
require_once('rabbitMQ/get_host_info.inc');
require_once('rabbitMQ/rabbitMQLib.inc');

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header('Location: index.html');
    exit();
}

$username = $_POST["username"];
$password = $_POST["password"];

if (empty($username) || empty($password) || strlen($username) > 64 || strlen($password) > 64) {
    header('Location: index.html');
    exit();
}

$request = array();
$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
$request['type'] = 'login';
$request['username'] = $username;
$request['password'] = $password;

$response = $client->send_request($request);

header('Location: index.html');
exit();
