#!/usr/bin/php
<?php
require_once('logger.php');
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('../MySQL/MySQL.php');

$server = new rabbitMQServer("rabbitMQ.ini", "Apache");

echo PHP_EOL . "STARTED" . PHP_EOL;

$server->process_requests('requestProcessor');

echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function login(string $username, string $password): bool|array
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

function register(string $username, string $password): bool
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

function getUser(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return [];
    }

    $existsStatement = $connection->prepare("SELECT Username FROM Users WHERE ID = ?");
    $existsStatement->bind_param("i", $userID);
    $existsStatement->execute();
    return $existsStatement->get_result()->fetch_assoc();
}

function checkSession(int $sessionID, int $userID): array|bool
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

function logout(int $sessionID, int $userID): bool
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

function linkSteamAccount(int $userID, int $steamID): bool
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

function unlinkSteamAccount(int $userID): bool
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

function saveCatalog(int $userID, string $title, array $ratings, array $names): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $catalogInsertStatement = $connection->prepare("INSERT INTO Catalogs (Title, UserID) VALUES (?, ?)");
    $catalogInsertStatement->bind_param("si", $title, $userID);
    if (!$catalogInsertStatement->execute()) {
        return false;
    }

    $catalogID = $catalogInsertStatement->insert_id;

    foreach ($ratings as $appID => $rating) {
        $gameName = $names[$appID];

        $insertGameStatement = $connection->prepare("INSERT INTO Games (AppID, Name) VALUES (?, ?) ON DUPLICATE KEY UPDATE Name = VALUES(Name)");
        $insertGameStatement->bind_param("is", $appID, $gameName);
        $insertGameStatement->execute();

        $catalogGameInsertStatement = $connection->prepare("INSERT INTO Catalog_Games (CatalogID, AppID, Rating) VALUES (?, ?, ?)");
        $catalogGameInsertStatement->bind_param("iii", $catalogID, $appID, $rating);
        if (!$catalogGameInsertStatement->execute()) {
            return false;
        }
    }

    return true;
}

function getUserCatalogs(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $catalogSelectStatement = $connection->prepare("SELECT CatalogID, Title FROM Catalogs WHERE UserID = ?");
    $catalogSelectStatement->bind_param("i", $userID);
    $catalogSelectStatement->execute();
    $catalogResult = $catalogSelectStatement->get_result();

    if ($catalogResult->num_rows === 0) {
        return [];
    }

    $allCatalogs = $catalogResult->fetch_all(MYSQLI_ASSOC);

    $catalogs = [];
    foreach ($allCatalogs as $catalog) {
        $catalogID = $catalog['CatalogID'];

        $catalogGamesStatement = $connection->prepare("
            SELECT 
                g.AppID, 
                g.Name, 
                g.Tags, 
                cg.Rating,
                ug.Playtime
            FROM Catalog_Games cg 
            INNER JOIN Games g ON cg.AppID = g.AppID 
            LEFT JOIN User_Games ug ON g.AppID = ug.AppID AND ug.UserID = ?
            WHERE cg.CatalogID = ?
        ");

        $catalogGamesStatement->bind_param("ii", $userID, $catalogID);
        $catalogGamesStatement->execute();
        $gamesResult = $catalogGamesStatement->get_result();
        $catalog['games'] = $gamesResult->fetch_all(MYSQLI_ASSOC);
        $catalogs[] = $catalog;
    }

    return $catalogs;
}

function getCatalog(int $catalogID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $catalogSelectStatment = $connection->prepare("SELECT CatalogID, Title, UserID FROM Catalogs WHERE CatalogID = ?");
    $catalogSelectStatment->bind_param("i", $catalogID);
    $catalogSelectStatment->execute();
    $catalog = $catalogSelectStatment->get_result()->fetch_assoc();

    if (!$catalog) {
        return [];
    }

    $catalogGamesStatement = $connection->prepare("
            SELECT 
                g.AppID, 
                g.Name, 
                g.Tags, 
                cg.Rating,
                ug.Playtime
            FROM Catalog_Games cg 
            INNER JOIN Games g ON cg.AppID = g.AppID 
            LEFT JOIN User_Games ug ON g.AppID = ug.AppID AND ug.UserID = ?
            WHERE cg.CatalogID = ?
    ");

    $catalogGamesStatement->bind_param("ii", $catalog['UserID'], $catalogID);
    $catalogGamesStatement->execute();
    $gamesResult = $catalogGamesStatement->get_result();
    $catalog['games'] = $gamesResult->fetch_all(MYSQLI_ASSOC);

    return $catalog;
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

function followUser(int $followerID, int $followedID): bool
{
    if ($followerID === $followedID) {
        return false;
    }

    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $existsStatement = $connection->prepare("SELECT FollowerID, FollowedID FROM Followers WHERE FollowerID = ? AND FollowedID = ?");
    $existsStatement->bind_param("ii", $followerID, $followedID);
    $existsStatement->execute();

    if ($existsStatement->get_result()->num_rows > 0) {
        return false;
    }

    $insertFollowerStatement = $connection->prepare("INSERT INTO Followers (FollowerID, FollowedID) VALUEs (?, ?)");
    $insertFollowerStatement->bind_param("ii", $followerID, $followedID);
    $insertFollowerStatement->execute();
    return $insertFollowerStatement->affected_rows > 0;
}

function unfollowUser(int $followerID, int $followedID): bool
{
    if ($followerID === $followedID) {
        return false;
    }

    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $deleteFollowerStatement = $connection->prepare("DELETE FROM Followers WHERE FollowerID = ? AND FollowedID = ?");
    $deleteFollowerStatement->bind_param("ii", $followerID, $followedID);
    $deleteFollowerStatement->execute();
    return $deleteFollowerStatement->affected_rows > 0;
}

function getUserFollowers(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $selectFollowersStatement = $connection->prepare("SELECT FollowerID FROM Followers WHERE FollowedID = ?");
    $selectFollowersStatement->bind_param("i", $userID);
    $selectFollowersStatement->execute();

    $result = $selectFollowersStatement->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserFollowing(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $selectFollowingStatement = $connection->prepare("SELECT FollowedID, Created FROM Followers WHERE FollowerID = ?");
    $selectFollowingStatement->bind_param("i", $userID);
    $selectFollowingStatement->execute();

    $result = $selectFollowingStatement->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function addCatalogComment(int $catalogID, int $userID, string $comment): bool
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $insertCommentStatement = $connection->prepare("INSERT INTO Catalog_Comments (CatalogID, UserID, Text) VALUEs (?, ?, ?)");
    $insertCommentStatement->bind_param("iis", $catalogID, $userID, $comment);
    $insertCommentStatement->execute();
    return $insertCommentStatement->affected_rows > 0;
}

function getCatalogComments(int $catalogID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $selectStatement = $connection->prepare("SELECT UserID, Text, Created FROM Catalog_Comments WHERE CatalogID = ?");
    $selectStatement->bind_param("i", $catalogID);
    $selectStatement->execute();
    return $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function storeUserGame(int $userID, int $appID, string $name, int $playtime): bool
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $emptyTags = json_encode([]);
    $insertGameStatement = $connection->prepare("INSERT INTO Games (AppID, Name, Tags) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Name = VALUES(Name)");
    $insertGameStatement->bind_param("iss", $appID, $name, $emptyTags);
    $insertGameStatement->execute();

    $insertUserGameStatement = $connection->prepare("INSERT INTO User_Games (UserID, AppID, Playtime) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Playtime = VALUES(Playtime)");
    $insertUserGameStatement->bind_param("iii", $userID, $appID, $playtime);
    $insertUserGameStatement->execute();

    return $insertUserGameStatement->affected_rows > 0;
}

function getUserGames(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $selectGamesStatement = $connection->prepare("
        SELECT 
            g.AppID, 
            g.Name, 
            g.Tags, 
            ug.Playtime 
        FROM User_Games ug
        INNER JOIN Games g ON ug.AppID = g.AppID
        WHERE ug.UserID = ?
    ");

    $selectGamesStatement->bind_param("i", $userID);
    $selectGamesStatement->execute();
    $result = $selectGamesStatement->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function storeGameTags(int $appID, array $tags): bool
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $tagsJson = json_encode($tags);

    $updateTagsStatement = $connection->prepare("UPDATE Games SET Tags = ? WHERE AppID = ?");
    $updateTagsStatement->bind_param("si", $tagsJson, $appID);
    $updateTagsStatement->execute();

    return $updateTagsStatement->affected_rows > 0;
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
        'getcatalog' => getCatalog($request['catalogid']),
        'getallusers' => getAllUsers(),
        'getuserfollowers' => getuserFollowers($request['userid']),
        'getuserfollowing' => getUserFollowing($request['userid']),
        'unfollowuser' => unfollowUser($request['userid'], $request['followid']),
        'followuser' => followUser($request['userid'], $request['followid']),
        'storeusergame' => storeUserGame($request['userid'], $request['appid'], $request['name'], $request['playtime']),
        'getusergames' => getUserGames($request['userid']),
        'storegametags' => storeGameTags($request['appid'], $request['tags']),
        'addcatalogcomment' => addCatalogComment($request['catalogid'], $request['userid'], $request['comment']),
        'getcatalogcomments' => getCatalogComments($request['catalogid']),
        'getuser' => getUser($request['userid']),
        default => false,
    };

}