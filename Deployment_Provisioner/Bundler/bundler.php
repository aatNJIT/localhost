#!/usr/bin/php
<?php
require_once('../RabbitMQ/RabbitClient.php');

while (true) {
    $type = strtolower(readline("Type? (WEBSERVER/BROKER/DATASOURCE/EXIT): "));
    echo "\n";

    switch ($type) {
        case 'webserver':
        case 'broker':
        case 'datasource':
            $sourcePath = readline("Enter source directory path: ");
            echo "\n";

            $allBundles = RabbitClient::getConnection()->send_request([
                    "type" => "getbundlesbytype",
                    "bundletype" => $type
            ]);

            if (is_array($allBundles) && !empty($allBundles)) {
                echo "Current Bundles: \n";
                for ($i = 0; $i < count($allBundles); $i++) {
                    $bundle = $allBundles[$i];
                    echo $bundle['Name'] . ' | v' . $bundle['Version'] . ' | ' . $bundle['Status'] . "\n";
                }
            }

            echo "\n";
            if (is_array($allBundles) && !empty($allBundles)) {
                $bundleName = readline("Enter existing bundle name OR new bundle name (NO SPACES): ");
            } else {
                $bundleName = readline("Enter new bundle name (NO SPACES): ");
            }

            if (is_array($allBundles) && !empty($allBundles)) {
                $existingVersions = [];

                foreach ($allBundles as $existingBundle) {
                    if ($existingBundle['Name'] === $bundleName) {
                        $existingVersions[] = $existingBundle;
                    }
                }

                if (!empty($existingVersions)) {
                    $latestBundle = $existingVersions[0];
                    $nextVersion = $latestBundle['Version'] + 1;

                    echo "\n";
                    echo "WARNING: Bundle '$bundleName' already exists\n";
                    echo "Latest: v{$latestBundle['Version']} ({$latestBundle['Status']}), Total versions: " . count($existingVersions) . "\n";
                    echo "Creating this bundle will create v{$nextVersion} with status NEW.\n";
                    $continueInput = strtolower(readline("Do you wish to continue? (Y/N): "));
                    echo "\n";

                    if ($continueInput !== 'y') {
                        break;
                    }
                }
            }

            echo "\n";
            $deployUserInput = strtolower(readline("Deploy? (Y/N): "));
            echo "\n";

            if ($deployUserInput === 'y') {
                $config = parse_ini_file('../RabbitMQ/rabbitMQ.ini', true);
                $deploymentHost = $config['Deployment']['BROKER_HOST'];
                $deploymentUser = $config['Deployment']['DEPLOYMENT_USER'];
                $deploymentPath = $config['Deployment']['DEPLOYMENT_PATH'];

                $version = RabbitClient::getConnection()->send_request([
                        "type" => "storebundle",
                        "bundlename" => $bundleName,
                        "bundletype" => $type
                ]);

                if ($version === -1) {
                    echo "Failed to register bundle in database.\n";
                    break;
                }

                $zipPath = createZip($type, $sourcePath, $bundleName, $version);
                if (!$zipPath) {
                    echo "Failed to create zip file.\n";
                    break;
                }

                $remotePath = "$deploymentUser@$deploymentHost:$deploymentPath/$type/";
                echo "Deploying bundle to $deploymentUser@$deploymentHost...\n";

                $scp = "scp " . escapeshellarg($zipPath) . ' ' . escapeshellarg($remotePath);
                exec($scp, $output, $resultCode);

                if ($resultCode === 0) {
                    echo "Bundle successfully deployed to $deploymentUser@$deploymentHost\n";
                } else {
                    echo "Failed to deploy bundle to $deploymentUser@$deploymentHost [$resultCode]\n";
                }
            }

            break;
        case 'exit':
            exit();
        default:
            echo "Invalid deployment type.\n\n";
            break;
    }
}

function createZip($type, $filePath, $bundleName, $version): string|false
{
    $typeDir = dirname(__FILE__) . '/' . $type;
    if (!is_dir($typeDir) && !mkdir($typeDir, 0777, true)) {
        echo "Failed to create directory: $typeDir\n";
        return false;
    }

    $zip = new ZipArchive();
    $zipFilename = $typeDir . '/' . $bundleName . '_v' . $version . '.zip';

    if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo "Couldn't open zip file\n";
        return false;
    }

    if (is_file($filePath)) {
        $zip->addFile($filePath, basename($filePath));
    } else if (is_dir($filePath)) {
        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($filePath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $realPath = $file->getRealPath();
            $relativePath = substr($realPath, strlen($filePath) + 1);
            $zip->addFile($realPath, $relativePath);
        }
    } else {
        echo "Path is neither a file nor a directory\n";
        $zip->close();
        return false;
    }

    $commands = getCommandsForType($type, $bundleName, $version);
    if (!empty($commands)) {
        $zip->addFromString('deployment_commands.txt', $commands);
    }

    $zip->close();
    return $zipFilename;
}

function getCommandsForType($type, $bundleName, $version): string
{
    $zipFileName = $bundleName . '_v' . $version . '.zip';
    $commands = [
            'webserver' => "
                sudo rm -rf /var/www/sample/*
                sudo unzip -o -q $zipFileName -d /var/www/sample/
                sudo systemctl restart apache2",
            'broker' => "
                sudo rm -rf /var/Broker/*
                sudo unzip -o -q $zipFileName -d /var/Broker/
                sudo systemctl restart rabbitmq-server",
            'datasource' => "
                sudo rm -rf /var/Datasource/*
                sudo unzip -o -q $zipFileName -d /var/Datasource/",
    ];
    return $commands[$type] ?? '';
}