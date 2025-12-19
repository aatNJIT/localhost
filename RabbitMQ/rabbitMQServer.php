#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('../MySQL/MySQL.php');
require_once('/usr/share/php/libphp-phpmailer/autoload.php');
require_once('logger.php');


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$env = parse_ini_file('../.env');

if (!$env || !isset($env['SMTP_USERNAME']) || !isset($env['SMTP_PASSWORD'])) {
    echo "Missing OTP .env File";
    return false;
}

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
    if (!$user || !password_verify($password, $user['Password'])) {
        return false;
    }

    try {
        // let's pretend like this is secure
        $sessionID = random_int(PHP_INT_MIN, PHP_INT_MAX);
    } catch (Exception) {
        return false;
    }

    $userID = $user['ID'];
    $userSteamID = $user['SteamID'];
    $sessionStatement = $connection->prepare("INSERT INTO Sessions (SessionID, UserID, Created, Activity) VALUES (?, ?, NOW(), NOW())");
    $sessionStatement->bind_param("ii", $sessionID, $userID);
    $sessionStatement->execute();

    return array(
            'userid' => $userID,
            'username' => $username,
            'steamid' => $userSteamID,
            'sessionid' => $sessionID
    );
}

function register(string $username, string $email, string $password): bool
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

    $emailExistsStatement = $connection->prepare("SELECT ID FROM Users WHERE Email = ?");
    $emailExistsStatement->bind_param("s", $email);
    $emailExistsStatement->execute();

    if ($emailExistsStatement->get_result()->num_rows > 0) {
        return false;
    }

    $statement = $connection->prepare("INSERT INTO Users (Username, Email, Password) VALUES (?, ?, ?)");
    $statement->bind_param("sss", $username, $email, $password);
    $statement->execute();
    return true;
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

    if ($connection->errno !== 0) {
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
    return true;
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
    return true;
}

function saveCatalog(int $userID, string $title, array $ratings): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    foreach ($ratings as $appID => $rating) {
        $gameExistsStatement = $connection->prepare("SELECT AppID FROM Games WHERE AppID = ?");
        $gameExistsStatement->bind_param("i", $appID);
        $gameExistsStatement->execute();
        if ($gameExistsStatement->get_result()->num_rows <= 0) {
            return false;
        }
    }

    $catalogInsertStatement = $connection->prepare("INSERT INTO Catalogs (Title, UserID) VALUES (?, ?)");
    $catalogInsertStatement->bind_param("si", $title, $userID);
    if (!$catalogInsertStatement->execute()) {
        return false;
    }

    $catalogID = $catalogInsertStatement->insert_id;

    foreach ($ratings as $appID => $rating) {
        $gameExistsStatement = $connection->prepare("INSERT INTO Catalog_Games (CatalogID, AppID, Rating) VALUES (?, ?, ?)");
        $gameExistsStatement->bind_param("iii", $catalogID, $appID, $rating);
        if (!$gameExistsStatement->execute()) {
            return false;
        }
    }

    return true;
}

function getUserCatalogs(int $userID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno != 0) {
        return [];
    }

    $catalogSelectStatement = $connection->prepare("SELECT CatalogID, Title FROM Catalogs WHERE UserID = ?");
    $catalogSelectStatement->bind_param("i", $userID);
    $catalogSelectStatement->execute();
    return $catalogSelectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
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
                cg.Rating
            FROM Catalog_Games cg 
            INNER JOIN Games g ON cg.AppID = g.AppID 
            WHERE cg.CatalogID = ?
    ");

    $catalogGamesStatement->bind_param("i", $catalogID);
    $catalogGamesStatement->execute();
    $gamesResult = $catalogGamesStatement->get_result();
    $catalog['games'] = $gamesResult->fetch_all(MYSQLI_ASSOC);

    return $catalog;
}

function getAllUsers(): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno != 0) {
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

    if ($connection->connect_errno != 0) {
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
    return true;
}

function unfollowUser(int $followerID, int $followedID): bool
{
    if ($followerID === $followedID) {
        return false;
    }

    $connection = MySQL::getConnection();

    if ($connection->connect_errno != 0) {
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

    if ($connection->connect_errno != 0) {
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

    if ($connection->connect_errno != 0) {
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

    if ($connection->connect_errno != 0) {
        return false;
    }

    $existsStatement = $connection->prepare("SELECT CatalogID FROM Catalogs WHERE CatalogID = ?");
    $existsStatement->bind_param("i", $catalogID);
    $existsStatement->execute();

    if ($existsStatement->get_result()->num_rows <= 0) {
        return false;
    }

    $insertCommentStatement = $connection->prepare("INSERT INTO Catalog_Comments (CatalogID, UserID, Text) VALUEs (?, ?, ?)");
    $insertCommentStatement->bind_param("iis", $catalogID, $userID, $comment);
    $insertCommentStatement->execute();
    return true;
}

function getCatalogComments(int $catalogID): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno != 0) {
        return [];
    }

    $selectStatement = $connection->prepare("SELECT UserID, Text, Created FROM Catalog_Comments WHERE CatalogID = ?");
    $selectStatement->bind_param("i", $catalogID);
    $selectStatement->execute();
    return $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getGames($limit, $offset): array
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return [];
    }

    $selectStatement = $connection->prepare("SELECT AppID, Name, Tags, Description FROM Games ORDER BY AppID LIMIT ? OFFSET ?");

    if (!$selectStatement) {
        return [];
    }

    $selectStatement->bind_param("ii", $limit, $offset);
    $selectStatement->execute();
    return $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getInfoForGames($appIDs): array
{
    if (empty($appIDs)) {
        return [];
    }

    $connection = MySQL::getConnection();
    if ($connection->errno != 0) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($appIDs), '?'));
    $selectStatement = $connection->prepare("SELECT AppID, Name, Tags, Description FROM Games WHERE AppID IN ($placeholders)");

    if (!$selectStatement) {
        return [];
    }

    $types = str_repeat('i', count($appIDs));
    $selectStatement->bind_param($types, ...$appIDs);
    $selectStatement->execute();
    return $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function searchGames($name, $limit): array
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return [];
    }

    $selectStatement = $connection->prepare("SELECT AppID, Name, Tags, Description FROM Games WHERE Name LIKE ? ORDER BY Name LIMIT ?");

    if (!$selectStatement) {
        return [];
    }

    $like = '%' . $name . '%';
    $selectStatement->bind_param("si", $like, $limit);
    $selectStatement->execute();
    return $selectStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function send2FALogin(array $request): bool|array
{
    $username = $request['username'] ?? '';
    $password = $request['password'] ?? '';

    if (empty($username) || empty($password)) {
        return false;
    }

    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $statement = $connection->prepare("SELECT * FROM Users WHERE Username = ?");
    $statement->bind_param("s", $username);
    $statement->execute();

    $user = $statement->get_result()->fetch_assoc();

    if ($user == null || !password_verify($password, $user['Password'])) {
        return false;
    }

    try {
        $otp = random_int(100000, 999999);
    } catch (Exception) {
        return false;
    }

    $otp_expiry = date("Y-m-d H:i:s", strtotime("+1 minute"));
    $userID = $user['ID'];

    $updateStatement = $connection->prepare("UPDATE Users SET OTP = ?, OTP_Expiry = ? WHERE ID = ?");
    $updateStatement->bind_param("ssi", $otp, $otp_expiry, $userID);
    $updateStatement->execute();

    $email = $user['Email'];

    if (!empty($email)) {
        sendOTPEmail($email, $otp, $username);
    }

    return [
            'userid' => $userID,
            'username' => $username,
            'message' => 'OTP sent to your email'
    ];
}

function verify2FALogin(array $request): bool|array
{
    $userID = $request['userid'] ?? 0;
    $otp = $request['otp'] ?? '';

    if (empty($userID) || empty($otp)) {
        return false;
    }

    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $statement = $connection->prepare("SELECT * FROM Users WHERE ID = ? AND OTP = ?");
    $statement->bind_param("is", $userID, $otp);
    $statement->execute();

    $user = $statement->get_result()->fetch_assoc();

    if (!$user) {
        return false;
    }

    $otp_expiry = strtotime($user['OTP_Expiry']);
    if ($otp_expiry < time()) {
        return false;
    }

    $clearOTPStatement = $connection->prepare("UPDATE Users SET OTP = NULL, OTP_Expiry = NULL WHERE ID = ?");
    $clearOTPStatement->bind_param("i", $userID);
    $clearOTPStatement->execute();

    try {
        $sessionID = random_int(PHP_INT_MIN, PHP_INT_MAX);
    } catch (Exception) {
        return false;
    }

    $sessionStatement = $connection->prepare("INSERT INTO Sessions (SessionID, UserID, Created, Activity) VALUES (?, ?, NOW(), NOW())");
    $sessionStatement->bind_param("ii", $sessionID, $userID);
    $sessionStatement->execute();

    return [
            'userid' => $userID,
            'username' => $user['Username'],
            'steamid' => $user['SteamID'],
            'sessionid' => $sessionID
    ];
}

function sendOTPEmail(string $to, string $otp, string $username): bool
{
    global $env;
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $env['SMTP_USERNAME'];
        $mail->Password = $env['SMTP_PASSWORD'];
        $mail->Port = $env['SMTP_PORT'] ?? 465;
        $mail->SMTPSecure = $env['SMTP_SECURE'] ?? 'ssl';
        $mail->isHTML(true);
        $mail->setFrom($env['SMTP_FROM_EMAIL'] ?? 'localhost@gmail.com', $env['SMTP_FROM_NAME'] ?? '2FA System');
        $mail->addAddress($to, $username);
        $mail->Subject = 'Your OTP for Login';
        $mail->Body = "Hello $username,<br><br>Your OTP is: <b>$otp</b><br><br>This code will expire in 5 minutes.";
        return $mail->send();
    } catch (PHPMailerException) {
        return false;
    }
}

function sendUserMesssage($senderUserID, $receiverUserID, $message): bool
{
    $connection = MySQL::getConnection();

    if ($connection->errno != 0) {
        return false;
    }

    $insertStatemenent = $connection->prepare("INSERT INTO Messages (SenderID, ReceiverID, Text) VALUES (?, ? ,?)");
    $insertStatemenent->bind_param("iis", $senderUserID, $receiverUserID, $message);
    $insertStatemenent->execute();
    return true;
}

function getMessagesBetweenUsers($user1ID, $user2ID, $limit = 50, $offset = 0): array
{
    $connection = MySQL::getConnection();

    if ($connection->connect_errno != 0) {
        return [];
    }

    $limit = (int)$limit;
    $offset = (int)$offset;

    $query = "
        SELECT 
            m.ID,
            m.SenderID,
            sender.Username AS SenderUsername,
            m.ReceiverID,
            receiver.Username AS ReceiverUsername,
            m.Text
        FROM Messages m
        JOIN Users sender ON m.SenderID = sender.ID
        JOIN Users receiver ON m.ReceiverID = receiver.ID
        WHERE (m.SenderID = ? AND m.ReceiverID = ?) OR (m.SenderID = ? AND m.ReceiverID = ?)
        ORDER BY m.ID ASC
        LIMIT $limit OFFSET $offset
    ";

    $selectStatement = $connection->prepare($query);
    $selectStatement->bind_param("iiii", $user1ID, $user2ID, $user2ID, $user1ID);
    $selectStatement->execute();
    $result = $selectStatement->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function requestProcessor($request): bool|array
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    return match ($type) {
        'login' => login($request['username'], $request['password']),
        'register' => register($request['username'], $request['email'], $request['password']),
        'session' => checkSession($request['sessionid'], $request['userid']),
        'logout' => logout($request['sessionid'], $request['userid']),
        'link' => linkSteamAccount($request['userid'], $request['steamid']),
        'unlink' => unlinkSteamAccount($request['userid']),
        'savecatalog' => saveCatalog($request['userid'], $request['title'], $request['ratings']),
        'getusercatalogs' => getUserCatalogs($request['userid']),
        'getcatalog' => getCatalog($request['catalogid']),
        'getallusers' => getAllUsers(),
        'games' => getGames($request['limit'], $request['offset']),
        'searchgames' => searchGames($request['name'], $request['limit']),
        'getinfoforgames' => getInfoForGames($request['appids']),
        'getuserfollowers' => getUserFollowers($request['userid']),
        'getuserfollowing' => getUserFollowing($request['userid']),
        'unfollowuser' => unfollowUser($request['userid'], $request['followid']),
        'followuser' => followUser($request['userid'], $request['followid']),
        'addcatalogcomment' => addCatalogComment($request['catalogid'], $request['userid'], $request['comment']),
        'getcatalogcomments' => getCatalogComments($request['catalogid']),
        'getuser' => getUser($request['userid']),
        'sendmessage' => sendUserMesssage($request['senderuserid'], $request['receiveruserid'], $request['message']),
        'getmessagesbetweenusers' => getMessagesBetweenUsers($request['senderuserid'], $request['receiveruserid'], $request['limit'], $request['offset']),
        '2fa_login' => send2FALogin($request),
        '2fa_verify' => verify2FALogin($request),
        default => false,
    };
}
