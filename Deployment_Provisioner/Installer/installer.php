#!/usr/bin/php
<?php
require_once('../RabbitMQ/get_host_info.inc');
require_once('../RabbitMQ/rabbitMQLib.inc');

$iniPath = __DIR__ . '/../RabbitMQ/rabbitMQ.ini';
if (!file_exists($iniPath)) {
    echo "Config file not found at: $iniPath\n";
    exit();
}

$config = parse_ini_file($iniPath, true);
$server = new rabbitMQServer("rabbitMQ.ini", 'Datasource Qa');
echo PHP_EOL . "STARTED" . PHP_EOL;
$server->process_requests('requestProcessor');
echo PHP_EOL . "ENDED" . PHP_EOL;
exit();

function deployBundle($bundleType, $bundleEnvironment, $bundlePath): bool
{
    global $config;
    $deploymentHost = $config['Deployment']['BROKER_HOST'];
    $deploymentUser = $config['Deployment']['DEPLOYMENT_USER'];
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
    $rabbitMqIniPath = findFileInZip($bundleZip, 'rabbitmq.ini');
    $rabbitMqIni = $rabbitMqIniPath ? $bundleZip->getFromName($rabbitMqIniPath) : false;
    
    if (!chdir($localDownloadDirectory)) {
        echo "Failed to change directory to $localDownloadDirectory\n";
        return false;
    }
    
    echo $bundleType;
    echo $bundleEnvironment;

    if ($rabbitMqIni && strlen($rabbitMqIni) > 0) {
        $brokerHostToUse = $config[ucwords($bundleType) . ' ' . ucwords($bundleEnvironment)]['TARGET_BROKER_HOST'];
        
        if (empty($brokerHostToUse)) {
            echo "Missing TARGET_BROKER_HOST for " . $bundleType . ' ' . $bundleEnvironment . "\n";
            return false;
        }
        
        $updatedRabbitMqIni = preg_replace('/^BROKER_HOST\s*=\s*.*/m', 'BROKER_HOST = ' . $brokerHostToUse, $rabbitMqIni);
        $bundleZip->deleteName($rabbitMqIniPath);
        $bundleZip->addFromString($rabbitMqIniPath, $updatedRabbitMqIni);
    } else {
        echo "Warning: rabbitMQ.ini not found in bundle\n";
    }
    
    $bundleZip->close();
    
    if ($deploymentCommands && strlen($deploymentCommands) > 0) {
        echo "Executing deployment commands\n";
        $commandLines = array_filter(array_map('trim', explode("\n", $deploymentCommands)));
        
        foreach ($commandLines as $command) {
            if (empty($command)) continue;
            exec($command, $output, $resultCode);
            
            if ($resultCode !== 0) {
                echo "Failed to execute command: $command [$resultCode]\n";
            }
        }
    } else {
        echo "Warning: deployment_commands.txt not found in bundle\n";
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
        'deploybundle' => deployBundle($request['bundletype'], $request['bundleenvironment'], $request['bundlepath']),
        default => false,
    };
}

function findFileInZip(ZipArchive $zip, string $filename): ?string
{
    $filenameLower = strtolower($filename);
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $filePath = $stat['name'];
        if (strtolower(basename($filePath)) === $filenameLower) {
            return $filePath;
        }
    }
    return null;
}