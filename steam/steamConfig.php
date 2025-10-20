<?php

$steamAuth['apikey'] = "3403607747D087E4DEA7DE9F64435880"; // Your Steam WebAPI-Key found at https://steamcommunity.com/dev/apikey
$steamAuth['domainname'] = "www.it490.com"; // The main URL of your website displayed in the login page
$steamAuth['logoutpage'] = ""; // Page to redirect to after a successfull logout (from the directory the SteamAuth-folder is located in) - NO slash at the beginning!
$steamAuth['loginpage'] = "steam/consumers/linkSteamAccount.php"; // Page to redirect to after a successfull login (from the directory the SteamAuth-folder is located in) - NO slash at the beginning!

if (empty($steamAuth['apikey'])) {die("<div style='display: block; width: 100%; background-color: red; text-align: center;'>SteamAuth:<br>Please supply an API-Key!<br>Find this in steam/steamConfig.php, Find the '<b>\$steam['apikey']</b>' Array. </div>");}
if (empty($steamAuth['domainname'])) {$steamAuth['domainname'] = $_SERVER['SERVER_NAME'];}
if (empty($steamAuth['logoutpage'])) {$steamAuth['logoutpage'] = $_SERVER['PHP_SELF'];}
if (empty($steamAuth['loginpage'])) {$steamAuth['loginpage'] = $_SERVER['PHP_SELF'];}
