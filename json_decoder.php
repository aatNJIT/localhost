<?php
// function to fetch API JSON
function getJson($url) {
    $response = @file_get_contents($url);
    return $response ? json_decode($response, true) : null;
}