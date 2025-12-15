#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$server = new rabbitMQServer("rabbitMQ.ini", "SteamAPI");
echo PHP_EOL . "STARTED" . PHP_EOL;

$env = parse_ini_file('.env');

if (!$env || empty($env['STEAM_API_KEY'])) {
    echo "Missing STEAM_API_KEY in .env file" . PHP_EOL;
    exit();
}

$apiKey = $env['STEAM_API_KEY'];
$server->process_requests('requestProcessor');

echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function getAllGames($lastAppID = 0): array
{
    echo $lastAppID;
    global $apiKey;
    $url = "https://api.steampowered.com/IStoreService/GetAppList/v1/?key=$apiKey&include_games=true&include_dlc=false&include_hardware=false&include_videos=false&include_software=false&max_results=50000&last_appid=$lastAppID";

    $context = stream_context_create([
            'http' => [
                    'timeout' => 60,
                    'user_agent' => 'Mozilla/5.0'
            ]
    ]);

    $response = file_get_contents($url, false, $context);

    if (!$response) {
        return [];
    }

    $data = json_decode($response, true);

    if (!isset($data['response']['apps'])) {
        return [];
    }

    return $data['response']['apps'];
}

function getUserGames(string $steamID): array
{
    global $apiKey;
    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=$apiKey&steamid=$steamID&include_appinfo=1&include_played_free_games=1";

    $response = file_get_contents($url);
    $content = json_decode($response, true);

    $games = [];
    $count = 0;

    if (isset($content['response']['games'])) {
        $games = $content['response']['games'];
        $count = $content['response']['game_count'];
    }

    return [
            'games' => $games,
            'count' => $count
    ];
}

function getUserProfile(string $steamID): array
{
    global $apiKey;
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apiKey&steamids=$steamID";

    $response = file_get_contents($url);
    $content = json_decode($response, true);

    if (!isset($content['response']['players'][0])) {
        return [];
    }

    $player = $content['response']['players'][0];

    return [
            'profile' => [
                    'steamid' => $player['steamid'] ?? '',
                    'communityvisibilitystate' => $player['communityvisibilitystate'] ?? '',
                    'profilestate' => $player['profilestate'] ?? '',
                    'personaname' => $player['personaname'] ?? '',
                    'lastlogoff' => $player['lastlogoff'] ?? '',
                    'profileurl' => $player['profileurl'] ?? '',
                    'avatar' => $player['avatar'] ?? '',
                    'avatarmedium' => $player['avatarmedium'] ?? '',
                    'avatarfull' => $player['avatarfull'] ?? '',
                    'personastate' => $player['personastate'] ?? '',
                    'realname' => $player['realname'] ?? 'Real name not given',
                    'primaryclanid' => $player['primaryclanid'] ?? '',
                    'timecreated' => $player['timecreated'] ?? '',
                    'loccountrycode' => $player['loccountrycode'] ?? '',
                    'locstatecode' => $player['locstatecode'] ?? '',
                    'loccityid' => $player['loccityid'] ?? ''
            ],
    ];
}

function getGameTags(int $appID): array
{
    $appDetails = "https://store.steampowered.com/api/appdetails?appids=$appID";
    $context = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'Mozilla/5.0']]);

    $response = file_get_contents($appDetails, false, $context);
    if (!$response) {
        return [];
    }

    $content = json_decode($response, true);

    if (!isset($content[$appID]['success']) || $content[$appID]['success'] !== true) {
        return [];
    }

    if (!isset($content[$appID]['data'])) {
        return [];
    }

    $data = $content[$appID]['data'];
    $genres = $data['genres'] ?? [];
    $categories = $data['categories'] ?? [];

    $tags = [];
    foreach ($genres as $genre) {
        if (isset($genre['description'])) {
            $tags[] = $genre['description'];
        }
    }
    foreach ($categories as $category) {
        if (isset($category['description'])) {
            $tags[] = $category['description'];
        }
    }

    return array_values(array_unique($tags));
}

function requestProcessor($request)
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    if ($type == 'profile') {
        return getuserProfile($request['steamid']);
    } else if ($type == 'tags') {
        return getGameTags($request['appid']);
    } else if ($type == 'getallgames') {
        return getAllGames($request['lastappid']);
    } else if ($type == 'getusergames') {
        return getUserGames($request['steamid']);
    }

    return false;
}