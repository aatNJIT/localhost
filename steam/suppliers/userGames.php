<?php
if (empty($_SESSION['steam_games_uptodate'])) {
    require 'steam/steamConfig.php';
    $url = file_get_contents("https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=".$steamAuth['apikey']."&steamid=".$_SESSION['steamid']."&include_appinfo=1&include_played_free_games=1");
    $content = json_decode($url, true);

    if (isset($content['response']['games'])) {
        $_SESSION['steam_games'] = $content['response']['games'];
        $_SESSION['steam_game_count'] = $content['response']['game_count'];
    } else {
        $_SESSION['steam_games'] = [];
        $_SESSION['steam_game_count'] = 0;
    }

    $_SESSION['steam_games_uptodate'] = time();
}

$steamgames['games'] = $_SESSION['steam_games'];
$steamgames['game_count'] = $_SESSION['steam_game_count'];
$steamgames['uptodate'] = $_SESSION['steam_games_uptodate'];