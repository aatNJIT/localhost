#!/usr/bin/php
<?php
require_once('../RabbitMQ/get_host_info.inc');
require_once('../RabbitMQ/rabbitMQLib.inc');
date_default_timezone_set('America/New_York');

$iniPath = __DIR__ . '/../RabbitMQ/rabbitMQ.ini';
if (!file_exists($iniPath)) {
    echo "Config file not found at: $iniPath\n";
    exit();
}

$config = parse_ini_file($iniPath, true);

$server = new rabbitMQServer("rabbitMQ.ini", "Webserver Qa");
echo PHP_EOL . "STARTED" . PHP_EOL;
$server->process_requests('requestProcessor');
echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function deployBundle($bundleType, $bundlePath): bool
{
    global $config;

    $deploymentHost = $config['Webserver Qa']['BROKER_HOST'];
    $deploymentUser = $config['Webserver Qa']['DEPLOYMENT_USER'];

    $localDownloadDirectory = __DIR__ . '/' . $bundleType;
    if (!is_dir($localDownloadDirectory)) {
        if (!mkdir($localDownloadDirectory, 0777, true)) {
            echo "Failed to create directory: $localDownloadDirectory\n";
            return false;
        }
    }

    $scp = "scp " . escapeshellarg("$deploymentUser@$deploymentHost:$bundlePath") . ' ' . escapeshellarg($localDownloadDirectory);
    exec($scp, $output, $resultCode);

    if ($resultCode !== 0) {
        echo "Failed to download bundle from $deploymentHost [$resultCode]\n";
        return false;
    }

    $bundleZip = new ZipArchive();
    $deployedBundleFileName = $localDownloadDirectory . '/' . basename($bundlePath);

    if ($bundleZip->open($deployedBundleFileName) !== TRUE) {
        echo "Failed to open bundle zip file\n";
        return false;
    }

    $originalDir = getcwd();
    $deploymentCommands = $bundleZip->getFromName('deployment_commands.txt');
    $bundleZip->close();

    if (!chdir($localDownloadDirectory)) {
        echo "Failed to change directory to $localDownloadDirectory\n";
        return false;
    }

    if ($deploymentCommands && strlen($deploymentCommands) > 0) {
        $commandLines = array_filter(array_map('trim', explode("\n", $deploymentCommands)));
        foreach ($commandLines as $command) {
            if (empty($command)) continue;
            exec($command, $output, $resultCode);
            if ($resultCode !== 0) {
                echo "Failed to execute command: $command [$resultCode]\n";
            }
        }
    }

    chdir($originalDir);
    return true;
}

function requestProcessor($request): bool
{
    echo var_dump($request) . PHP_EOL;

    if (!isset($request['type'])) {
        return false;
    }

    $type = $request['type'];

    return match ($type) {
        'deploybundle' => deployBundle($request['bundletype'], $request['bundlepath']),
        default => false,
    };
}