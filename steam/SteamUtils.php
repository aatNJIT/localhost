<?php
class SteamUtils {
    public static function getGameTime($game): float {
        return round($game['playtime_forever'] / 60, 1);
    }

    public static function getAppImage($appId): string {
        return "https://cdn.cloudflare.steamstatic.com/steam/apps/$appId/header.jpg";
    }

}