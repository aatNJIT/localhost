<?php
session_start();
require_once('session.php');
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');
require_once('steam/SteamUtils.php');

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    header("Location: profile.php");
    exit();
}

// Get user's Steam games
$gamesRequest = ['type' => RequestType::GAMES, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
$gamesResponse = RabbitClient::getConnection("SteamAPI")->send_request($gamesRequest);
$userGames = $gamesResponse['games'] ?? [];

// Get all user's owned game IDs for filtering
$ownedGameIds = array_column($userGames, 'appid');

// Get top 10 most recently played games (by playtime_2weeks, then playtime_forever)
$recentGames = $userGames;
usort($recentGames, function($a, $b) {
    $a2weeks = $a['playtime_2weeks'] ?? 0;
    $b2weeks = $b['playtime_2weeks'] ?? 0;
    if ($a2weeks != $b2weeks) {
        return $b2weeks - $a2weeks;
    }
    return $b['playtime_forever'] - $a['playtime_forever'];
});
$recentGames = array_slice($recentGames, 0, 10);

// Get top 5 most played games for display
$topPlayedGames = array_slice($recentGames, 0, 5);

// Collect tags from user's recent games using SteamSpy API (has tags data)
$tagCounts = [];
$gameTagsMap = [];

foreach ($recentGames as $game) {
    $appId = $game['appid'];
    $steamSpyUrl = @file_get_contents("https://steamspy.com/api.php?request=appdetails&appid=$appId");
    if (!$steamSpyUrl) continue;
    
    $steamSpyData = json_decode($steamSpyUrl, true);
    if (!$steamSpyData || empty($steamSpyData['tags'])) continue;
    
    // Tags come as associative array: tag => vote count
    $tags = array_keys($steamSpyData['tags']);
    $gameTagsMap[$appId] = array_slice($tags, 0, 5); // Keep top 5 tags per game
    
    // Weight tags by playtime
    $weight = max(1, ($game['playtime_2weeks'] ?? 0) + ($game['playtime_forever'] / 60));
    foreach ($tags as $tag) {
        if (!isset($tagCounts[$tag])) {
            $tagCounts[$tag] = 0;
        }
        $tagCounts[$tag] += $weight;
    }
    
    usleep(200000); // 0.2 second delay to avoid rate limiting
}

// Sort tags by frequency and get top tags
arsort($tagCounts);
$topTags = array_slice(array_keys($tagCounts), 0, 5);

// Get recommended games based on top tags using SteamSpy
$suggestedGames = [];

if (!empty($topTags)) {
    // Use the most common tag to find similar games
    $topTag = urlencode($topTags[0]);
    $tagGamesUrl = @file_get_contents("https://steamspy.com/api.php?request=tag&tag=$topTag");
    
    if ($tagGamesUrl) {
        $tagGames = json_decode($tagGamesUrl, true);
        
        if (is_array($tagGames)) {
            // Sort by score (owners * positive reviews ratio)
            uasort($tagGames, function($a, $b) {
                $scoreA = ($a['positive'] ?? 0) - ($a['negative'] ?? 0);
                $scoreB = ($b['positive'] ?? 0) - ($b['negative'] ?? 0);
                return $scoreB - $scoreA;
            });
            
            $count = 0;
            foreach ($tagGames as $appId => $gameData) {
                // Skip games user already owns
                if (in_array((int)$appId, $ownedGameIds)) continue;
                
                $suggestedGames[] = [
                    'appid' => $appId,
                    'name' => $gameData['name'] ?? 'Unknown',
                    'header_image' => SteamUtils::getAppImage($appId),
                    'positive' => $gameData['positive'] ?? 0,
                    'negative' => $gameData['negative'] ?? 0,
                    'tags' => $topTags
                ];
                
                $count++;
                if ($count >= 10) break;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <title>IT-490 - Game Recommendations</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css"/>
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css"/>
</head>

<body>
<main class="main">
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

    <!-- Your Top Games -->
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-fire"></i> Your Most Played Games</h3>
        <?php if (!empty($topPlayedGames)): ?>
            <?php foreach ($topPlayedGames as $game): ?>
                <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                    <img src="<?= SteamUtils::getAppImage($game['appid']) ?>" 
                         alt="<?= htmlspecialchars($game['name']) ?>" 
                         style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                    <div style="text-align: left;">
                        <strong><?= htmlspecialchars($game['name']) ?></strong><br>
                        <small style="color: var(--pico-muted-color);">
                            <i class="fa-solid fa-clock"></i> 
                            <?= SteamUtils::getGameTime($game) ?> hours played
                        </small>
                        <?php if (isset($gameTagsMap[$game['appid']])): ?>
                            <br><small style="color: var(--pico-primary);">
                                <i class="fa-solid fa-tags"></i> <?= htmlspecialchars(implode(', ', array_slice($gameTagsMap[$game['appid']], 0, 3))) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--pico-muted-color);">
                No games played yet. Start playing to get recommendations!
            </p>
        <?php endif; ?>
    </article>

    <!-- Your Top Tags -->
    <?php if (!empty($topTags)): ?>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-tags"></i> Your Favorite Genres</h3>
        <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
            Based on your most played games
        </p>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem;">
            <?php foreach ($topTags as $tag): ?>
                <span style="background: var(--pico-primary-background); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem;">
                    <?= htmlspecialchars($tag) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </article>
    <?php endif; ?>

    <!-- Recommended Games Based on Tags -->
    <?php if (!empty($suggestedGames)): ?>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-lightbulb"></i> Recommended For You</h3>
        <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
            Games similar to what you play most
            <?php if (!empty($topTags)): ?>
                (<?= htmlspecialchars($topTags[0]) ?>)
            <?php endif; ?>
        </p>
        <?php foreach ($suggestedGames as $game): ?>
            <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                <img src="<?= htmlspecialchars($game['header_image']) ?>" 
                     alt="<?= htmlspecialchars($game['name']) ?>"
                     style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                <div style="text-align: left;">
                    <strong><?= htmlspecialchars($game['name']) ?></strong><br>
                    <small style="color: var(--pico-muted-color);">
                        <?php 
                        $total = $game['positive'] + $game['negative'];
                        $rating = $total > 0 ? round(($game['positive'] / $total) * 100) : 0;
                        ?>
                        <?php if ($total > 0): ?>
                            <i class="fa-solid fa-thumbs-up"></i> <?= $rating ?>% positive (<?= number_format($total) ?> reviews)
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </article>
    <?php else: ?>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-lightbulb"></i> Recommended For You</h3>
        <p style="color: var(--pico-muted-color);">
            Play more games to get personalized recommendations based on your favorite genres!
        </p>
    </article>
    <?php endif; ?>

    <!-- Quick Actions -->
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <p style="color: var(--pico-muted-color);">
            Recommendations are based on the genres/tags of your most played games.
        </p>
        <a href="profile.php" role="button" class="primary">
            <i class="fa-solid fa-gamepad"></i> View Your Library
        </a>
    </article>

</main>
</body>
</html>
