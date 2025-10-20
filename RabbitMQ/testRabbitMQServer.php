#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('../MySQL/MySQL.php');

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo PHP_EOL . "STARTED" . PHP_EOL;

$server->process_requests('requestProcessor');

echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function login($username, $password)
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $statement = $connection->prepare("SELECT * FROM Users WHERE Username = ?");
    $statement->bind_param("s", $username);
    $statement->execute();

    $user = $statement->get_result()->fetch_assoc();
    if ($user != null && password_verify($password, $user['Password'])) {
        try {
            // let's pretend like this is secure
            $sessionID = random_int(PHP_INT_MIN, PHP_INT_MAX);
        } catch (Exception $e) {
            return false;
        }

        $userID = $user['ID'];
        $sessionStatement = $connection->prepare("INSERT INTO Sessions (sessionID, userID, created, activity) VALUES (?, ?, NOW(), NOW())");
        $sessionStatement->bind_param("ii", $sessionID, $userID);
        $sessionStatement->execute();

        return array(
                'userID' => $userID,
                'username' => $username,
                'steamID' => $user['SteamID'],
                'sessionID' => $sessionID
        );
    } else {
        return false;
    }
}

function register($username, $password): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $existsStatement = $connection->prepare("SELECT ID FROM Users WHERE Username = ?");
    $existsStatement->bind_param("s", $username);
    $existsStatement->execute();

    if ($existsStatement->get_result()->num_rows > 0) {
        return false;
    }

    $statement = $connection->prepare("INSERT INTO Users (Username, Password) VALUES (?, ?)");
    $statement->bind_param("ss", $username, $password);

    if ($statement->execute()) {
        return true;
    } else {
        return false;
    }
}

function checkSession($sessionID, $userID): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $validSessionStatement = $connection->prepare("SELECT activity,  TIMESTAMPDIFF(SECOND, activity, NOW()) as inactivityAsSeconds FROM Sessions WHERE sessionID = ? AND userID = ?");
    $validSessionStatement->bind_param("ii", $sessionID, $userID);
    $validSessionStatement->execute();

    $result = $validSessionStatement->get_result();
    $session = $result->fetch_assoc();

    if (!$session) {
        return false;
    }

    $inactivity = $session['inactivityAsSeconds'];
    if ($inactivity > 3600) {
        $deleteStatement = $connection->prepare("DELETE FROM Sessions WHERE sessionID = ? AND userID = ?");
        $deleteStatement->bind_param("ii", $sessionID, $userID);
        $deleteStatement->execute();
        return false;
    }

    $updateStatement = $connection->prepare("UPDATE Sessions SET activity = NOW() WHERE sessionID = ? AND userID = ?");
    $updateStatement->bind_param("ii", $sessionID, $userID);
    $updateStatement->execute();
    return true;
}

function logout($sessionID, $userID): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $deleteStatement = $connection->prepare("DELETE FROM Sessions WHERE sessionID = ? AND userID = ?");
    $deleteStatement->bind_param("ii", $sessionID, $userID);
    $deleteStatement->execute();
    return true;
}

function linkSteamAccount($userID, $steamID): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $userExistsStatement = $connection->prepare("SELECT ID FROM Users WHERE ID = ?");
    $userExistsStatement->bind_param("i", $userID);
    $userExistsStatement->execute();

    if ($userExistsStatement->get_result()->num_rows <= 0) {
        return false;
    }

    $duplicateSteamIdStatement = $connection->prepare("SELECT ID FROM Users WHERE SteamID = ? AND ID != ?");
    $duplicateSteamIdStatement->bind_param("si", $steamID, $userID);
    $duplicateSteamIdStatement->execute();

    if ($duplicateSteamIdStatement->get_result()->num_rows > 0) {
        return false;
    }

    $updateStatement = $connection->prepare("UPDATE Users SET SteamID = ? WHERE ID = ?");
    $updateStatement->bind_param("si", $steamID, $userID);
    $updateStatement->execute();

    return $updateStatement->affected_rows > 0;
}

function unlinkSteamAccount($userID): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $existsStatement = $connection->prepare("SELECT ID FROM Users WHERE ID = ?");
    $existsStatement->bind_param("i", $userID);
    $existsStatement->execute();

    if ($existsStatement->get_result()->num_rows <= 0) {
        return false;
    }

    $updateStatement = $connection->prepare("UPDATE Users SET SteamID = NULL WHERE ID = ?");
    $updateStatement->bind_param("i", $userID);
    $updateStatement->execute();

    return $updateStatement->affected_rows > 0;
}

function requestProcessor($request)
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    if ($type == 'login') {
        return login($request['username'], $request['password']);
    } else if ($type == 'register') {
        return register($request['username'], $request['password']);
    } else if ($type == 'session') {
        return checkSession($request['sessionID'], $request['userID']);
    } else if ($type == 'logout') {
        return logout($request['sessionID'], $request['userID']);
    } else if ($type == 'link') {
        return linkSteamAccount($request['userID'], $request['steamID']);
    } else if ($type == 'unlink') {
        return unlinkSteamAccount($request['userID']);
    }

    return false;
}
