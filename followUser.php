<?php
session_start();
require_once('identifiers.php');
require_once('session.php');
require_once('steam/SteamUtils.php');

// Check if user has Steam linked
if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    header("Location: profile.php");
    exit();
}

// Get Steam API key
require 'steam/steamConfig.php';

// Fetch friends list from Steam API
$friendsUrl = @file_get_contents("https://api.steampowered.com/ISteamUser/GetFriendList/v1/?key=" . $steamAuth['apikey'] . "&steamid=" . $_SESSION[Identifiers::STEAM_ID]);
$friendsData = $friendsUrl ? json_decode($friendsUrl, true) : null;

$friends = [];

if ($friendsData && isset($friendsData['friendslist']['friends']) && !empty($friendsData['friendslist']['friends'])) {
    // Get friend Steam IDs
    $friendIds = array_map(function($f) { return $f['steamid']; }, $friendsData['friendslist']['friends']);
    $friendIdsStr = implode(',', $friendIds);
    
    // Fetch friend details from Steam API
    $friendDetailsUrl = @file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamAuth['apikey'] . "&steamids=" . $friendIdsStr);
    $friendDetails = $friendDetailsUrl ? json_decode($friendDetailsUrl, true) : null;
    
    if ($friendDetails && isset($friendDetails['response']['players'])) {
        $friends = $friendDetails['response']['players'];
    }
}

// Handle follow action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'follow') {
    require_once('rabbitMQ/RabbitClient.php');
    
    $userIDToFollow = $_POST[Identifiers::USER_ID] ?? null;
    $username = $_POST[Identifiers::USERNAME] ?? null;
    
    if ($userIDToFollow && $username) {
        $followerUserID = $_SESSION[Identifiers::USER_ID];
        
        if ($userIDToFollow != $followerUserID) {
            $request = array(
                'type' => RequestType::FOLLOW_USER,
                Identifiers::USER_ID => $followerUserID,
                'followid' => $userIDToFollow,
            );
            
            $response = RabbitClient::getConnection()->send_request($request);
            
            if (!$response) {
                $successMessage = 'Followed User: ' . htmlspecialchars($username);
            } else {
                $errorMessage = 'Error following user: ' . htmlspecialchars($username);
            }
        } else {
            $errorMessage = 'Cannot follow yourself';
        }
    } else {
        $errorMessage = 'Invalid user information';
    }
}

// Get list of users on the site to match with Steam friends
require_once('rabbitMQ/RabbitClient.php');
$usersRequest = ['type' => RequestType::GET_ALL_USERS];
$usersResponse = RabbitClient::getConnection()->send_request($usersRequest);
$siteUsers = is_array($usersResponse) ? $usersResponse : [];

// Create a map of Steam IDs to user accounts
$steamToUser = [];
foreach ($siteUsers as $user) {
    if (isset($user['SteamID']) && !empty($user['SteamID'])) {
        $steamToUser[$user['SteamID']] = $user;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Friends</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav style="justify-content: center">
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fa-solid fa-house"></i> Index
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="fa-solid fa-users"></i> Users
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                </li>
                <li>
                    <a href="browse.php">
                        <i class="fa-solid fa-gamepad"></i> Browse
                    </a>
                </li>
                <li>
                    <a href="createCatalog.php">
                        <i class="fa-solid fa-plus"></i> Create Catalog
                    </a>
                </li>
                <li>
                    <a href="recommendations.php">
                        <i class="fa-solid fa-lightbulb"></i> Recommendations
                    </a>
                </li>
                <li>
                    <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>">
                        <i class="fa-solid fa-list"></i> My Catalogs
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </article>

    <?php if (isset($successMessage)): ?>
        <article style="background-color: lightgreen; margin-top: 1rem; color: darkgreen; border: 2px green solid; text-align: center;">
            <?= $successMessage ?>
        </article>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <article style="background-color: indianred; margin-top: 1rem; color: darkred; border: 2px darkred solid; text-align: center;">
            <?= $errorMessage ?>
        </article>
    <?php endif; ?>

    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-user-group"></i> Your Steam Friends</h3>
        <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
            Friends from your Steam account who are registered on this site
        </p>

        <?php if (!empty($friends)): ?>
            <?php 
            $friendsOnSite = [];
            $friendsNotOnSite = [];
            
            foreach ($friends as $friend) {
                if (isset($steamToUser[$friend['steamid']])) {
                    $friendsOnSite[] = ['steam' => $friend, 'user' => $steamToUser[$friend['steamid']]];
                } else {
                    $friendsNotOnSite[] = $friend;
                }
            }
            ?>

            <?php if (!empty($friendsOnSite)): ?>
                <div style="margin-top: 1rem;">
                    <h4 style="text-align: left; padding-left: 0.5rem;">
                        <i class="fa-solid fa-check-circle"></i> Friends on This Site (<?= count($friendsOnSite) ?>)
                    </h4>
                    <?php foreach ($friendsOnSite as $friendData): ?>
                        <?php 
                        $friend = $friendData['steam'];
                        $siteUser = $friendData['user'];
                        ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                            <div style="display: flex; align-items: center; gap: 1rem; text-align: left;">
                                <img src="<?= htmlspecialchars($friend['avatarmedium']) ?>" 
                                     alt="Avatar" 
                                     style="width: 64px; height: 64px; border-radius: 4px;">
                                <div>
                                    <strong><?= htmlspecialchars($friend['personaname']) ?></strong><br>
                                    <small style="color: var(--pico-muted-color);">
                                        Username: <?= htmlspecialchars($siteUser['Username']) ?><br>
                                        <?php if ($friend['personastate'] == 1): ?>
                                            <span style="color: #57cbde;">● Online</span>
                                        <?php elseif ($friend['personastate'] == 0): ?>
                                            <span style="color: #898989;">● Offline</span>
                                        <?php else: ?>
                                            <span style="color: #c9b171;">● Away</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="viewUserCatalogs.php?userid=<?= $siteUser['ID'] ?>" 
                                   role="button" 
                                   class="secondary outline"
                                   style="margin: 0;">
                                    <i class="fa-solid fa-list"></i> Catalogs
                                </a>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="follow">
                                    <input type="hidden" name="<?= Identifiers::USER_ID ?>" value="<?= $siteUser['ID'] ?>">
                                    <input type="hidden" name="<?= Identifiers::USERNAME ?>" value="<?= htmlspecialchars($siteUser['Username']) ?>">
                                    <button type="submit" class="primary" style="margin: 0;">
                                        <i class="fa-solid fa-user-plus"></i> Follow
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($friendsNotOnSite)): ?>
                <div style="margin-top: 2rem;">
                    <h4 style="text-align: left; padding-left: 0.5rem;">
                        <i class="fa-solid fa-circle-info"></i> Other Steam Friends (<?= count($friendsNotOnSite) ?>)
                    </h4>
                    <p style="text-align: left; padding-left: 0.5rem; color: var(--pico-muted-color); font-size: 0.9rem;">
                        These friends are not registered on this site
                    </p>
                    <?php foreach (array_slice($friendsNotOnSite, 0, 10) as $friend): ?>
                        <div style="display: flex; align-items: center; padding: 0.6rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px; opacity: 0.7;">
                            <img src="<?= htmlspecialchars($friend['avatar']) ?>" 
                                 alt="Avatar" 
                                 style="width: 40px; height: 40px; border-radius: 4px; margin-right: 1rem;">
                            <div style="text-align: left;">
                                <strong><?= htmlspecialchars($friend['personaname']) ?></strong><br>
                                <small style="color: var(--pico-muted-color);">Not on this site</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($friendsNotOnSite) > 10): ?>
                        <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
                            ...and <?= count($friendsNotOnSite) - 10 ?> more
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p style="color: var(--pico-muted-color); margin-top: 2rem;">
                No Steam friends found. Add friends on Steam to see them here!
            </p>
        <?php endif; ?>
    </article>

    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <p style="color: var(--pico-muted-color);">
            This page shows your Steam friends. Follow friends who are registered on this site to stay connected!
        </p>
        <a href="users.php" role="button" class="secondary outline">
            <i class="fa-solid fa-users"></i> View All Users
        </a>
    </article>

</main>
</body>
</html>
