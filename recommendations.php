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


$catalogRequest = ['type' => RequestType::GET_USER_CATALOGS, Identifiers::USER_ID => $_SESSION[Identifiers::USER_ID]];
$catalogResponse = RabbitClient::getConnection()->send_request($catalogRequest);

// Build a map of game ratings from user's catalogs
$userRatings = [];
if (is_array($catalogResponse) && !empty($catalogResponse)) {
    foreach ($catalogResponse as $catalog) {
        if (!empty($catalog['games'])) {
            foreach ($catalog['games'] as $game) {
                $appid = $game['AppID'];
                $rating = $game['Rating'];
                
                if (!isset($userRatings[$appid]) || $rating > $userRatings[$appid]) {
                    $userRatings[$appid] = $rating;
                }
            }
        }
    }
}

// Get top 5 most played games (by playtime)
$topPlayedGames = $userGames;
usort($topPlayedGames, function($a, $b) {
    return $b['playtime_forever'] - $a['playtime_forever'];
});
$topPlayedGames = array_slice($topPlayedGames, 0, 5);

// Get top 5 highest rated games
$ratedGames = [];
foreach ($userGames as $game) {
    if (isset($userRatings[$game['appid']])) {
        $game['user_rating'] = $userRatings[$game['appid']];
        $ratedGames[] = $game;
    }
}
usort($ratedGames, function($a, $b) {
    return $b['user_rating'] - $a['user_rating'];
});
$topRatedGames = array_slice($ratedGames, 0, 5);


require 'steam/steamConfig.php';

// Get tags/genres for top games to find similar games
$recommendedGames = [];
$processedAppIds = [];


function getSimilarGames($appId, &$recommendations, &$processed): void
{
    
    $detailsUrl = @file_get_contents("https://store.steampowered.com/api/appdetails?appids=$appId");
    if (!$detailsUrl) return;
    
    $details = json_decode($detailsUrl, true);
    if (!isset($details[$appId]['success']) || !$details[$appId]['success']) return;
    
    $gameData = $details[$appId]['data'];
    $tags = [];
    
    
    if (isset($gameData['genres'])) {
        foreach ($gameData['genres'] as $genre) {
            $tags[] = $genre['description'];
        }
    }
    
    
    if (!empty($tags)) {
        $recommendations[$appId] = [
            'name' => $gameData['name'] ?? 'Unknown',
            'appid' => $appId,
            'tags' => $tags,
            'short_description' => $gameData['short_description'] ?? '',
            'header_image' => $gameData['header_image'] ?? ''
        ];
        $processed[] = $appId;
    }
    
    
    usleep(100000); // 0.1 second
}


$sourceGames = array_merge($topPlayedGames, $topRatedGames);
$sourceGames = array_unique($sourceGames, SORT_REGULAR);
$sourceGames = array_slice($sourceGames, 0, 5); 

foreach ($sourceGames as $game) {
    getSimilarGames($game['appid'], $recommendedGames, $processedAppIds);
}

// Get all user's owned game IDs for filtering
$ownedGameIds = array_column($userGames, 'appid');

// Fetch featured games from Steam as additional recommendations
$featuredUrl = @file_get_contents("https://store.steampowered.com/api/featured/");
$featuredData = $featuredUrl ? json_decode($featuredUrl, true) : null;
$suggestedGames = [];

if ($featuredData && isset($featuredData['featured_win'])) {
    foreach (array_slice($featuredData['featured_win'], 0, 10) as $featured) {
        $appId = $featured['id'];
        // Only suggest games user doesn't own
        if (!in_array($appId, $ownedGameIds)) {
            $suggestedGames[] = [
                'appid' => $appId,
                'name' => $featured['name'],
                'header_image' => $featured['header_image'] ?? SteamUtils::getAppImage($appId),
                'discount_percent' => $featured['discount_percent'] ?? 0,
                'final_price' => isset($featured['final_price']) ? ($featured['final_price'] / 100) : 0
            ];
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
<main style="padding-left: 10vh; padding-right: 10vh;">
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
                            <?php if (isset($userRatings[$game['appid']])): ?>
                                | <i class="fa-solid fa-star"></i> Rating: <?= $userRatings[$game['appid']] ?>/10
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--pico-muted-color);">
                No games played yet. Start playing to get recommendations!
            </p>
        <?php endif; ?>
    </article>

    <!-- Your Top Rated Games -->
    <?php if (!empty($topRatedGames)): ?>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-star"></i> Your Highest Rated Games</h3>
        <?php foreach ($topRatedGames as $game): ?>
            <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                <img src="<?= SteamUtils::getAppImage($game['appid']) ?>" 
                     alt="<?= htmlspecialchars($game['name']) ?>" 
                     style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                <div style="text-align: left;">
                    <strong><?= htmlspecialchars($game['name']) ?></strong><br>
                    <small style="color: var(--pico-muted-color);">
                        <i class="fa-solid fa-star"></i> Rating: <?= $game['user_rating'] ?>/10
                        | <i class="fa-solid fa-clock"></i> <?= SteamUtils::getGameTime($game) ?> hours played
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </article>
    <?php endif; ?>

    <!-- Featured/Suggested Games -->
    <?php if (!empty($suggestedGames)): ?>
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <h3><i class="fa-solid fa-lightbulb"></i> Popular Games You Don't Own</h3>
        <p style="color: var(--pico-muted-color); font-size: 0.9rem;">
            Trending games on Steam
        </p>
        <?php foreach (array_slice($suggestedGames, 0, 6) as $game): ?>
            <div style="display: flex; align-items: center; padding: 0.5rem; margin-bottom: 0.4rem; border: 1px solid var(--pico-form-element-border-color); border-radius: 4px;">
                <img src="<?= htmlspecialchars($game['header_image']) ?>" 
                     alt="<?= htmlspecialchars($game['name']) ?>"
                     style="max-width: 120px; border-radius: 2px; margin-right: 1rem;">
                <div style="text-align: left;">
                    <strong><?= htmlspecialchars($game['name']) ?></strong><br>
                    <small style="color: var(--pico-muted-color);">
                        <?php if ($game['discount_percent'] > 0): ?>
                            <span style="color: #4c9f38; font-weight: bold;">
                                -<?= $game['discount_percent'] ?>% | $<?= number_format($game['final_price'], 2) ?>
                            </span>
                        <?php elseif ($game['final_price'] > 0): ?>
                            $<?= number_format($game['final_price'], 2) ?>
                        <?php else: ?>
                            Free to Play
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </article>
    <?php endif; ?>

    <!-- Quick Actions -->
    <article style="margin-top: 1rem; text-align: center; border: 1px var(--pico-form-element-border-color) solid">
        <p style="color: var(--pico-muted-color);">
            Recommendations are based on your most played games and highest rated titles.
        </p>
        <a href="createCatalog.php" role="button" class="primary">
            <i class="fa-solid fa-star"></i> Rate More Games
        </a>
    </article>

</main>
</body>
</html>
