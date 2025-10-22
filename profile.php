<?php
session_start();
require('steam/steamAuth.php');
require_once('identifiers.php');
require_once('session.php');
require_once('steam/SteamUtils.php');
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Profile</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/profile-styles.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
</head>

<body>
<main class="container" style="padding-left: 1rem; padding-right: 1rem">
    <article style="border: 1px var(--pico-form-element-border-color) solid">
        <nav>
            <ul>
                <li>
                    <strong>IT-490 <?php echo '[' . $_SESSION['username'] . ']' ?></strong>
                </li>
            </ul>
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
                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): ?>
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
                <?php endif; ?>
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

    <?php if (!isset($_SESSION[Identifiers::STEAM_ID])): ?>
        <article style="margin-top: 1rem; text-align: center;">
            <h2>Link Your Steam Account</h2>
            <p>Connect your Steam account to view your profile and game library.</p>
            <a href='?login' role="button" class="primary">
                <i class="fa-solid fa-link"></i> Link Steam Account
            </a>
        </article>
    <?php else: ?>
        <?php
        // Get or update Steam profile data
        $sessionProfile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? null;
        $update = $sessionProfile === null || (isset($sessionProfile['profileupdatetime']) && time() - $sessionProfile['profileupdatetime'] > 300);

        if ($update) {
            $request = ['type' => RequestType::PROFILE, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
            $response = RabbitClient::getConnection("SteamAPI")->send_request($request);
            if (is_array($response) && !empty($response)) {
                $_SESSION[Identifiers::STEAM_PROFILE] = $response;
            }
        }

        $steamProfile = $_SESSION[Identifiers::STEAM_PROFILE][Identifiers::STEAM_PROFILE] ?? [];
        
        if (!empty($steamProfile)):
            // Fetch Steam games data
            $gamesRequest = ['type' => RequestType::GAMES, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
            $gamesResponse = RabbitClient::getConnection("SteamAPI")->send_request($gamesRequest);
            $steamgames = $gamesResponse ?? ['game_count' => 0, 'games' => []];
            
            // Fetch additional Steam data using direct API calls
            require 'steam/steamConfig.php';
            
            // Get Steam level
            $levelUrl = @file_get_contents("https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/?key=" . $steamAuth['apikey'] . "&steamid=" . $_SESSION[Identifiers::STEAM_ID]);
            $levelData = $levelUrl ? json_decode($levelUrl, true) : null;
            $steamLevel = $levelData['response']['player_level'] ?? 0;
            
            // Get badges
            $badgesUrl = @file_get_contents("https://api.steampowered.com/IPlayerService/GetBadges/v1/?key=" . $steamAuth['apikey'] . "&steamid=" . $_SESSION[Identifiers::STEAM_ID]);
            $badgesData = $badgesUrl ? json_decode($badgesUrl, true) : null;
            
            // Get friends list
            $friendsUrl = @file_get_contents("https://api.steampowered.com/ISteamUser/GetFriendList/v1/?key=" . $steamAuth['apikey'] . "&steamid=" . $_SESSION[Identifiers::STEAM_ID]);
            $friendsData = $friendsUrl ? json_decode($friendsUrl, true) : null;
            
            // Get recently played games
            $recentUrl = @file_get_contents("https://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1/?key=" . $steamAuth['apikey'] . "&steamid=" . $_SESSION[Identifiers::STEAM_ID] . "&count=6");
            $recentGames = $recentUrl ? json_decode($recentUrl, true) : null;
            
            // Determine online status
            $statusClass = 'status-offline';
            $statusText = 'Offline';
            if (isset($steamProfile['personastate'])) {
                if ($steamProfile['personastate'] == 1) {
                    $statusClass = 'status-online';
                    $statusText = 'Online';
                } elseif ($steamProfile['personastate'] == 3) {
                    $statusClass = 'status-away';
                    $statusText = 'Away';
                }
            }
        ?>

        <!-- Profile Header -->
        <div class="profile-header" style="margin-top: 1rem;">
            <div class="profile-avatar">
                <img src="<?php echo $steamProfile['avatar'] ?? 'https://via.placeholder.com/184'; ?>" alt="Steam Avatar">
                <div class="status-indicator <?php echo $statusClass; ?>" title="<?php echo $statusText; ?>"></div>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?php echo htmlspecialchars($steamProfile['personaname'] ?? 'Unknown'); ?></div>
                <?php if (isset($steamProfile['realname']) && !empty($steamProfile['realname']) && $steamProfile['realname'] !== 'Real name not given'): ?>
                    <div class="profile-realname"><?php echo htmlspecialchars($steamProfile['realname']); ?></div>
                <?php endif; ?>
                <div class="profile-level">
                    <i class="fa-solid fa-trophy"></i> Level <?php echo $steamLevel; ?>
                </div>
                <div style="margin-top: 1rem;">
                    <small style="color: var(--pico-muted-color);">
                        <i class="fa-solid fa-id-card"></i> Steam ID: <?php echo $steamProfile['steamid'] ?? 'N/A'; ?><br>
                        <?php if (isset($steamProfile['timecreated'])): ?>
                            <i class="fa-solid fa-calendar"></i> Member since: <?php echo date('F Y', $steamProfile['timecreated']); ?><br>
                        <?php endif; ?>
                        <?php if (isset($steamProfile['lastlogoff'])): ?>
                            <i class="fa-solid fa-clock"></i> Last online: <?php echo date('M d, Y g:i A', $steamProfile['lastlogoff']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <?php if (isset($steamProfile['profileurl'])): ?>
                        <a href="<?php echo $steamProfile['profileurl']; ?>" target="_blank" role="button" class="secondary outline">
                            <i class="fa-brands fa-steam"></i> View Steam Profile
                        </a>
                    <?php endif; ?>
                    <a href="steam/consumers/unlinkSteamAccount.php" role="button" class="contrast outline">
                        <i class="fa-solid fa-unlink"></i> Unlink Account
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <article style="border: 1px var(--pico-form-element-border-color) solid; margin-top: 1rem;">
            <header><h3><i class="fa-solid fa-chart-line"></i> Statistics</h3></header>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $steamgames['game_count'] ?? 0; ?></div>
                    <div class="stat-label">Games Owned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $steamLevel; ?></div>
                    <div class="stat-label">Steam Level</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        if ($badgesData && isset($badgesData['response']['badges'])) {
                            echo count($badgesData['response']['badges']);
                        } else {
                            echo '0';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Badges Earned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        if ($friendsData && isset($friendsData['friendslist']['friends'])) {
                            echo count($friendsData['friendslist']['friends']);
                        } else {
                            echo '0';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Friends</div>
                </div>
            </div>
        </article>

        <!-- Recently Played Games -->
        <?php if ($recentGames && isset($recentGames['response']['games']) && !empty($recentGames['response']['games'])): ?>
        <article style="border: 1px var(--pico-form-element-border-color) solid; margin-top: 1rem;">
            <header><h3><i class="fa-solid fa-clock"></i> Recently Played</h3></header>
            <div class="games-showcase">
                <?php foreach ($recentGames['response']['games'] as $game): ?>
                    <div class="game-mini">
                        <img src="<?php echo SteamUtils::getAppImage($game['appid']); ?>" 
                             alt="<?php echo htmlspecialchars($game['name']); ?>"
                             title="<?php echo htmlspecialchars($game['name']); ?>">
                        <div class="game-playtime">
                            <?php echo round($game['playtime_forever'] / 60, 1); ?>h played
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php endif; ?>

        <!-- Top Played Games -->
        <?php if (!empty($steamgames['games'])): 
            $topGames = array_slice($steamgames['games'], 0, 6);
            usort($topGames, function($a, $b) {
                return $b['playtime_forever'] - $a['playtime_forever'];
            });
        ?>
        <article style="border: 1px var(--pico-form-element-border-color) solid; margin-top: 1rem;">
            <header><h3><i class="fa-solid fa-fire"></i> Most Played Games</h3></header>
            <div class="games-showcase">
                <?php foreach ($topGames as $game): ?>
                    <div class="game-mini">
                        <img src="<?php echo SteamUtils::getAppImage($game['appid']); ?>" 
                             alt="<?php echo htmlspecialchars($game['name']); ?>"
                             title="<?php echo htmlspecialchars($game['name']); ?>">
                        <div class="game-playtime">
                            <?php echo SteamUtils::getGameTime($game); ?>h played
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php endif; ?>

        <!-- Badges -->
        <?php if ($badgesData && isset($badgesData['response']['badges']) && !empty($badgesData['response']['badges'])): 
            $displayBadges = array_slice($badgesData['response']['badges'], 0, 8);
        ?>
        <article style="border: 1px var(--pico-form-element-border-color) solid; margin-top: 1rem;">
            <header><h3><i class="fa-solid fa-award"></i> Recent Badges</h3></header>
            <div class="badges-container">
                <?php foreach ($displayBadges as $badge): ?>
                    <div class="badge-item">
                        <?php if (isset($badge['appid']) && $badge['appid'] > 0): ?>
                            <img src="https://cdn.cloudflare.steamstatic.com/steamcommunity/public/images/apps/<?php echo $badge['appid']; ?>/<?php echo $badge['communityitemid']; ?>.jpg" 
                                 alt="Badge" 
                                 onerror="this.src='https://via.placeholder.com/64?text=Badge'">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/64?text=Badge" alt="Badge">
                        <?php endif; ?>
                        <small>Level <?php echo $badge['level'] ?? 1; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php endif; ?>

        <!-- Friends List Preview -->
        <?php if ($friendsData && isset($friendsData['friendslist']['friends']) && !empty($friendsData['friendslist']['friends'])): 
            $displayFriends = array_slice($friendsData['friendslist']['friends'], 0, 12);
            
            // Fetch friend details
            $friendIds = array_map(function($f) { return $f['steamid']; }, $displayFriends);
            $friendIdsStr = implode(',', $friendIds);
            $friendDetailsUrl = @file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $steamAuth['apikey'] . "&steamids=" . $friendIdsStr);
            $friendDetails = $friendDetailsUrl ? json_decode($friendDetailsUrl, true) : null;
        ?>
        <?php if ($friendDetails && isset($friendDetails['response']['players'])): ?>
        <article style="border: 1px var(--pico-form-element-border-color) solid; margin-top: 1rem;">
            <header><h3><i class="fa-solid fa-users"></i> Friends</h3></header>
            <div class="friends-grid">
                <?php foreach ($friendDetails['response']['players'] as $friend): ?>
                    <div class="friend-card">
                        <img src="<?php echo $friend['avatar']; ?>" alt="Friend" class="friend-avatar">
                        <div class="friend-name"><?php echo htmlspecialchars($friend['personaname']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php endif; ?>
        <?php endif; ?>

        <?php else: ?>
            <article style="margin-top: 1rem; text-align: center;">
                <p>No Steam profile data found.</p>
            </article>
        <?php endif; ?>

    <?php endif; ?>
</main>
</body>
</html>
