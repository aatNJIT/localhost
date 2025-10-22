#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('../MySQL/MySQL.php');

$server = new rabbitMQServer("rabbitMQ.ini", "Apache");

echo PHP_EOL . "STARTED" . PHP_EOL;

$server->process_requests('requestProcessor');

echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function login($username, $password): bool|array
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
        $sessionStatement = $connection->prepare("INSERT INTO Sessions (SessionID, UserID, Created, Activity) VALUES (?, ?, NOW(), NOW())");
        $sessionStatement->bind_param("ii", $sessionID, $userID);
        $sessionStatement->execute();

        return array(
                'userid' => $userID,
                'username' => $username,
                'steamid' => $user['SteamID'],
                'sessionid' => $sessionID
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

function checkSession($sessionID, $userID): array|bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $validSessionStatement = $connection->prepare("SELECT Activity, TIMESTAMPDIFF(SECOND, activity, NOW()) as inactivityAsSeconds FROM Sessions WHERE SessionID = ? AND UserID = ?");
    $validSessionStatement->bind_param("ii", $sessionID, $userID);
    $validSessionStatement->execute();

    $result = $validSessionStatement->get_result();
    $session = $result->fetch_assoc();

    if (!$session) {
        return false;
    }

    $inactivity = $session['inactivityAsSeconds'];
    if ($inactivity > 3600) {
        $deleteStatement = $connection->prepare("DELETE FROM Sessions WHERE SessionID = ? AND UserID = ?");
        $deleteStatement->bind_param("ii", $sessionID, $userID);
        $deleteStatement->execute();
        return false;
    }

    $updateStatement = $connection->prepare("UPDATE Sessions SET Activity = NOW() WHERE SessionID = ? AND UserID = ?");
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

    $deleteStatement = $connection->prepare("DELETE FROM Sessions WHERE SessionID = ? AND UserID = ?");
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
    $duplicateSteamIdStatement->bind_param("ii", $steamID, $userID);
    $duplicateSteamIdStatement->execute();

    if ($duplicateSteamIdStatement->get_result()->num_rows > 0) {
        return false;
    }

    $updateStatement = $connection->prepare("UPDATE Users SET SteamID = ? WHERE ID = ?");
    $updateStatement->bind_param("ii", $steamID, $userID);
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

function saveCatalog($userID, $title, $ratings, $names): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $catalogInsertStatement = $connection->prepare("INSERT INTO Catalogs (Title, UserID) VALUES (?, ?)");
    $catalogInsertStatement->bind_param("si", $title, $userID);
    if (!$catalogInsertStatement->execute()) {
        return false;
    };

    $catalogID = $catalogInsertStatement->insert_id;

    foreach ($ratings as $appID => $rating) {
        $gameName = $names[$appID];
        $catalogGameInsertStatement = $connection->prepare("INSERT INTO Catalog_Games (CatalogID, GameName, AppID, Rating) VALUES (?, ?, ?, ?)");
        $catalogGameInsertStatement->bind_param("isii", $catalogID, $gameName, $appID, $rating);
        if (!$catalogGameInsertStatement->execute()) {
            $catalogDeleteStatement = $connection->prepare("DELETE FROM Catalogs WHERE UserID = ?");
            $catalogDeleteStatement->bind_param("i", $userID);
            $catalogDeleteStatement->execute();
            return false;
        }
    }

    return true;
}

function getUserCatalogs($userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $catalogSelectStmt = $connection->prepare("SELECT CatalogID, Title FROM Catalogs WHERE UserID = ?");
    $catalogSelectStmt->bind_param("i", $userID);
    $catalogSelectStmt->execute();
    $catalogResult = $catalogSelectStmt->get_result();

    if ($catalogResult->num_rows === 0) {
        return [];
    }

    $allCatalogs = $catalogResult->fetch_all(MYSQLI_ASSOC);

    $catalogs = [];
    foreach ($allCatalogs as $catalog) {
        $catalogID = $catalog['CatalogID'];
        $catalogGamesStmt = $connection->prepare("SELECT GameID, GameName, AppID, Rating FROM Catalog_Games WHERE CatalogID = ?");
        $catalogGamesStmt->bind_param("i", $catalogID);
        $catalogGamesStmt->execute();
        $gamesResult = $catalogGamesStmt->get_result();
        $catalog['games'] = $gamesResult->fetch_all(MYSQLI_ASSOC);
        $catalogs[] = $catalog;
    }

    return $catalogs;
}

function getAllUsers(): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $selectAllUsersStatement = $connection->query("SELECT ID, SteamID, Username FROM Users");
    return $selectAllUsersStatement->fetch_all(MYSQLI_ASSOC);
}

function followUser($userID, $followID): bool
{
    return false;
}

function requestProcessor($request)
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    return match ($type) {
        'login' => login($request['username'], $request['password']),
        'register' => register($request['username'], $request['password']),
        'session' => checkSession($request['sessionid'], $request['userid']),
        'logout' => logout($request['sessionid'], $request['userid']),
        'link' => linkSteamAccount($request['userid'], $request['steamid']),
        'unlink' => unlinkSteamAccount($request['userid']),
        'savecatalog' => saveCatalog($request['userid'], $request['title'], $request['ratings'], $request['names']),
        'getusercatalogs' => getUserCatalogs($request['userid']),
        'getallusers' => getAllUsers(),
        'followuser' => followUser($request['userid'], $request['followid']),
        default => false,
    };

}
