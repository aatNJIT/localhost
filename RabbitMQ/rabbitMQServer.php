#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$server = new rabbitMQServer("rabbitMQ.ini", "SteamAPI");
echo PHP_EOL . "STARTED" . PHP_EOL;
$server->process_requests('requestProcessor');
echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function getUserGames(string $steamid): array
{
    $env = parse_ini_file('../.env');
    $apiKey = $env['STEAM_API_KEY'];

    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/" . "?key=$apiKey&steamid=$steamid&include_appinfo=1&include_played_free_games=1";

    $response = file_get_contents($url);
    $content = json_decode($response, true);

    $games = [];
    $count = 0;

    if (isset($content['response']['games'])) {
        $games = $content['response']['games'];
        $count = $content['response']['game_count'] ?? count($games);
    }

    return [
            'games' => $games,
            'count' => $count,
            'gameupdatetime' => time(),
    ];
}

function getUserProfile(string $steamid): array
{
    $env = parse_ini_file('../.env');
    $apiKey = $env['STEAM_API_KEY'];

    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/" . "?key=$apiKey&steamids=$steamid";

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
                    'loccityid' => $player['loccityid'] ?? '',
                    'profileupdatetime' => time(),
            ],
    ];
}

function requestProcessor($request)
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    if ($type == 'games') {
        return getUserGames($request['steamid']);
    } else if ($type == 'profile') {
        return getuserProfile($request['steamid']);
    }

    return false;
}