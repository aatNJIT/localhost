#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('../MySQL/mySql.php');

$server = new rabbitMQServer("rabbitMQ.ini", "Deployment");
echo PHP_EOL . "STARTED" . PHP_EOL;
$server->process_requests('requestProcessor');
echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function storeBundle($bundleName, $bundleType): int
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return -1;
    }

    $existingBundleStatement = $connection->prepare("SELECT MAX(Version) as HighestVersion FROM Bundles WHERE Name = ? AND Type = ?");
    $existingBundleStatement->bind_param("ss", $bundleName, $bundleType);
    $existingBundleStatement->execute();
    $result = $existingBundleStatement->get_result();

    $newVersion = 1;
    if ($result->num_rows > 0) {
        $bundle = $result->fetch_assoc();
        if (isset($bundle['HighestVersion'])) {
            $newVersion = $bundle['HighestVersion'] + 1;
        }
    }

    $insertBundleStatement = $connection->prepare("INSERT INTO Bundles (Name, Type, Version, Status) VALUES (?, ?, ?, 'NEW')");
    $insertBundleStatement->bind_param("ssi", $bundleName, $bundleType, $newVersion);

    if ($insertBundleStatement->execute()) {
        return $newVersion;
    }

    return -1;
}

function updateBundleStatus($bundleName, $bundleType, $bundleVersion, $bundleStatus): bool
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return false;
    }

    $existsBundleStatement = $connection->prepare("SELECT * FROM Bundles WHERE Version = ? AND Name = ? AND Type = ?");
    $existsBundleStatement->bind_param("iss", $bundleVersion, $bundleName, $bundleType);
    $existsBundleStatement->execute();

    if ($existsBundleStatement->get_result()->num_rows <= 0) {
        return false;
    }

    $updateBundleStatement = $connection->prepare("UPDATE Bundles SET Status = ? WHERE Version = ? AND Name = ? AND Type = ?");
    $updateBundleStatement->bind_param("siss", $bundleStatus, $bundleVersion, $bundleName, $bundleType);
    $updateBundleStatement->execute();
    return true;
}

function getBundlesByTypeAndStatus($bundleStatus, $bundleType): array
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $bundlesByTypeAndStatusStatement = $connection->prepare("SELECT * FROM Bundles WHERE Status = ? AND Type = ? ORDER BY ID DESC");
    $bundlesByTypeAndStatusStatement->bind_param("ss", $bundleStatus, $bundleType);
    $bundlesByTypeAndStatusStatement->execute();
    return $bundlesByTypeAndStatusStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getBundleByNameAndType($bundleName, $bundleType): array
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $getBundleStatement = $connection->prepare("SELECT * FROM Bundles WHERE Name = ? AND Type = ? ORDER BY Version DESC LIMIT 1");
    $getBundleStatement->bind_param("ss", $bundleName, $bundleType);
    $getBundleStatement->execute();
    $result = $getBundleStatement->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return [];
}

function getBundlesByType($bundleType): array
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    $getBundleStatement = $connection->prepare("SELECT * FROM Bundles WHERE Type = ? ORDER BY Name, Version DESC");
    $getBundleStatement->bind_param("s", $bundleType);
    $getBundleStatement->execute();
    return $getBundleStatement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getLastSuccessfulBundle($bundleType, $environment): array
{
    $connection = mySql::getConnection();

    if ($connection->connect_errno !== 0) {
        return [];
    }

    if ($environment === 'qa') {
        $lastSuccessfulBundleStatement = $connection->prepare("SELECT * FROM Bundles WHERE Type = ? AND Status IN ('NEW', 'DEPLOYED_QA', 'PASSED') ORDER BY ID DESC LIMIT 1 OFFSET 1");
    } else {
        $lastSuccessfulBundleStatement = $connection->prepare("SELECT * FROM Bundles WHERE Type = ? AND Status IN ('PASSED', 'DEPLOYED_PROD') ORDER BY ID DESC LIMIT 1 OFFSET 1");
    }

    $lastSuccessfulBundleStatement->bind_param("s", $bundleType);
    $lastSuccessfulBundleStatement->execute();
    $result = $lastSuccessfulBundleStatement->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return [];
}

function requestProcessor($request): array|bool|int
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    if ($type === 'storebundle') {
        return storeBundle($request['bundlename'], $request['bundletype']);
    } else if ($type === 'updatebundlestatus') {
        return updateBundleStatus($request['bundlename'], $request['bundletype'], $request['bundleversion'], $request['bundlestatus']);
    } else if ($type === 'getbundlesbytypeandstatus') {
        return getBundlesByTypeAndStatus($request['bundlestatus'], $request['bundletype']);
    } elseif ($type === 'getbundlebynameandtype') {
        return getBundleByNameAndType($request['bundlename'], $request['bundletype']);
    } else if ($type === 'getbundlesbytype') {
        return getBundlesByType($request['bundletype']);
    } else if ($type === 'getlastsuccessfulbundle') {
        return getLastSuccessfulBundle($request['bundletype'], $request['environment']);
    }

    return false;
}
