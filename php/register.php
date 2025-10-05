<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../rabbitMQ/get_host_info.inc');
require_once('../rabbitMQ/rabbitMQLib.inc');
require_once('../rabbitMQ/RabbitClient.php');

echo "Reached register.php";

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header('Location: ../loggedin.html');
    exit();
}