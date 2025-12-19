<?php
session_start();
require_once('session.php');
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');
require_once('steam/SteamUtils.php');

if (!isset($_SESSION[Identifiers::STEAM_ID])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

try {
    $gamesRequest = ['type' => RequestType::GET_USER_GAMES, Identifiers::STEAM_ID => $_SESSION[Identifiers::STEAM_ID]];
    $gamesResponse = RabbitClient::getConnection("SteamAPI")->send_request($gamesRequest);

    $userGames = $gamesResponse['games'] ?? [];
    $ownedGameIds = array_column($userGames, 'appid');
    $recentGames = $userGames;

    usort($recentGames, function($a, $b) {
        return $b['playtime_forever'] - $a['playtime_forever'];
    });

    $recentGames = array_slice($recentGames, 0, 10);
    $topPlayedGames = array_slice($recentGames, 0, 5);

    $tagCounts = [];
    $gameTagsMap = [];

    foreach ($recentGames as $game) {
        $appId = $game['appid'];
        $steamSpyUrl = file_get_contents("https://steamspy.com/api.php?request=appdetails&appid=$appId");
        if (!$steamSpyUrl) continue;

        $steamSpyData = json_decode($steamSpyUrl, true);
        if (!$steamSpyData || empty($steamSpyData['tags'])) continue;

        $tags = array_keys($steamSpyData['tags']);
        $gameTagsMap[$appId] = array_slice($tags, 0, 5);

        $weight = max(1, ($game['playtime_forever'] / 60));
        foreach ($tags as $tag) {
            if (!isset($tagCounts[$tag])) $tagCounts[$tag] = 0;
            $tagCounts[$tag] += $weight;
        }

        usleep(200000);
    }

    arsort($tagCounts);
    $topTags = array_slice(array_keys($tagCounts), 0, 5);
    $suggestedGames = [];

    if (!empty($topTags)) {
        $topTag = urlencode($topTags[0]);
        $tagGamesUrl = file_get_contents("https://steamspy.com/api.php?request=tag&tag=$topTag");

        if ($tagGamesUrl) {
            $tagGames = json_decode($tagGamesUrl, true);

            if (is_array($tagGames)) {
                uasort($tagGames, function($a, $b) {
                    $scoreA = ($a['positive'] ?? 0) - ($a['negative'] ?? 0);
                    $scoreB = ($b['positive'] ?? 0) - ($b['negative'] ?? 0);
                    return $scoreB - $scoreA;
                });

                $count = 0;
                foreach ($tagGames as $appId => $gameData) {
                    if (in_array((int)$appId, $ownedGameIds)) continue;

                    $total = ($gameData['positive'] ?? 0) + ($gameData['negative'] ?? 0);
                    $rating = $total > 0 ? round((($gameData['positive'] ?? 0) / $total) * 100) : 0;

                    $suggestedGames[] = [
                        'appid' => $appId,
                        'name' => $gameData['name'] ?? 'Unknown',
                        'image' => SteamUtils::getAppImage($appId),
                        'rating' => $rating,
                        'reviews' => $total
                    ];

                    $count++;
                    if ($count >= 10) break;
                }
            }
        }
    }

    $formattedTopGames = array_map(function($game) use ($gameTagsMap) {
        $tags = isset($gameTagsMap[$game['appid']]) ?
            implode(', ', array_slice($gameTagsMap[$game['appid']], 0, 8)) :
            null;

        return [
            'appid' => $game['appid'],
            'name' => $game['name'],
            'image' => SteamUtils::getAppImage($game['appid']),
            'hours' => SteamUtils::getGameTime($game),
            'tags' => $tags
        ];
    }, $topPlayedGames);

    echo json_encode([
        'topGames' => $formattedTopGames,
        'topTags' => $topTags,
        'suggestions' => $suggestedGames
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load recommendations: ' . $e->getMessage()]);
}