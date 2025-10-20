<?php
require_once('json_decoder.php');

// load steam api key from .env
$env = parse_ini_file('.env');
$steam_api_key = $env['steam_api_key'] ?? null;

if (!$steam_api_key) {
    die("Error: steam_api_key not found in .env file");
}

if (isset($_GET['steamid'])) {
    $steamid = $_GET['steamid'];

    // SteamID validation
    if (!preg_match('/^\d{17}$/', $steamid)) {
        echo "Steam ID must be 17 digits. Try again.";
        exit;
    }

    // API Call to get user info
    $steam_userUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=$steam_api_key&steamids=$steamid";
    $steam_userData = getJson($steam_userUrl);

    if (!$steam_userData || empty($steam_userData['response']['players'][0])) {
        echo "Could not find user. Check SteamID.";
        exit;
    }

    $steam_user = $steam_userData['response']['players'][0];
    echo "<h3>Steam Username: " . htmlspecialchars($steam_user['personaname']) . "</h3>";

    // API Call to get game list
    $gamesUrl = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=$steam_api_key&steamid=$steamid&include_appinfo=1&include_played_free_games=1";
    $gamesList = getJson($gamesUrl);

    if (empty($gamesList['response']['games'])) {
        echo "No games found or the profile may be private.";
        exit;
    }

    echo "<h4>Total Games: " . $gamesList['response']['game_count'] . "</h4>";

    // Display games PHP Output
    foreach ($gamesList['response']['games'] as $game) {
        $appid = $game['appid'];
        $name = htmlspecialchars($game['name']);
        $playtime = round($game['playtime_forever'] / 60, 2); // converts minutes to hours

        echo "------------------------------------<br>";
        echo "Game: $name<br>";
        echo "App ID: $appid<br>";
        echo "Total Playtime: $playtime hours<br>";
        echo "<br>";
    }
}
?>
