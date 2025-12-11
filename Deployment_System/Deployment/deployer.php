#!/usr/bin/php
<?php
require_once('../RabbitMQ/RabbitClient.php');
$config = parse_ini_file('../RabbitMQ/rabbitMQ.ini', true);
$deploymentPath = $config['Deployment']['DEPLOYMENT_PATH'];

while (true) {
    $environment = strtolower(readline("Environment? (QA/PROD/EXIT): "));
    echo "\n";

    if ($environment === 'exit') {
        exit();
    } elseif ($environment !== 'qa' && $environment !== 'prod') {
        echo "Invalid bundle environment chosen\n";
        continue;
    }

    $type = strtolower(readline("Type? (WEBSERVER/BROKER/DATASOURCE/EXIT): "));
    echo "\n";

    switch ($type) {
        case 'datasource':
        case 'broker':
        case 'webserver':
            $deploymentMode = strtolower(readline("Deployment mode? (NORMAL/ROLLBACK): "));
            echo "\n";

            $isRollback = false;
            $currentBundle = null;

            if ($deploymentMode === 'rollback') {
                $currentBundle = RabbitClient::getConnection()->send_request([
                        "type" => "getbundlesbytypeandstatus",
                        "bundlestatus" => $environment === 'qa' ? 'DEPLOYED_QA' : 'DEPLOYED_PROD',
                        "bundletype" => $type
                ]);

                if (is_array($currentBundle) && !empty($currentBundle)) {
                    $currentBundle = $currentBundle[0];
                }

                $selectedBundle = RabbitClient::getConnection()->send_request([
                        "type" => "getlastsuccessfulbundle",
                        "bundletype" => $type,
                        "environment" => $environment
                ]);

                if (!is_array($selectedBundle) || empty($selectedBundle)) {
                    echo "No valid bundles found to rollback to\n";
                    break;
                }

                echo "Currently deployed:\n";
                if ($currentBundle) {
                    echo "  {$currentBundle['Name']} (v{$currentBundle['Version']})\n";
                }

                echo "\nWill rollback to:\n";
                echo "  {$selectedBundle['Name']} (v{$selectedBundle['Version']})\n";
                echo "\n";

                $confirm = strtolower(readline("Confirm rollback? (Y/N): "));
                echo "\n";

                if ($confirm !== 'y') {
                    echo "Rollback cancelled\n";
                    break;
                }

                $isRollback = true;
            } else {
                $bundles = RabbitClient::getConnection()->send_request([
                        "type" => "getbundlesbytypeandstatus",
                        "bundlestatus" => $environment === 'qa' ? 'NEW' : 'PASSED',
                        "bundletype" => $type
                ]);

                if (!is_array($bundles) || empty($bundles)) {
                    echo "No valid bundles found\n";
                    break;
                }

                echo "Bundles:\n";
                foreach ($bundles as $i => $bundle) {
                    echo "  [$i] {$bundle['Name']} | v{$bundle['Version']} | {$bundle['Status']}\n";
                }

                echo "\n";
                $bundleIndex = readline("Choose bundle index to deploy: ");

                if (!is_numeric($bundleIndex) || $bundleIndex < 0 || $bundleIndex >= count($bundles)) {
                    echo "Invalid bundle chosen\n";
                    break;
                }

                echo "\n";
                $selectedBundle = $bundles[$bundleIndex];
            }

            $selectedBundlePath = $deploymentPath . '/' . $type . '/' . $selectedBundle['Name'] . '_v' . $selectedBundle['Version'] . '.zip';

            $server = ucwords($type) . ' ' . ucwords($environment);
            echo "Deploying on $server...\n";

            $result = RabbitClient::getConnection($server)->send_request([
                    "type" => "deploybundle",
                    "bundletype" => $type,
		            "bundleenvironment" => $environment,
                    "bundlepath" => $selectedBundlePath
            ]);

            if ($result) {
                echo "Bundle successfully deployed\n";

                if ($isRollback && $currentBundle != null) {
                    RabbitClient::getConnection()->send_request([
                            "type" => "updatebundlestatus",
                            "bundlename" => $currentBundle['Name'],
                            "bundletype" => $type,
                            "bundleversion" => $currentBundle['Version'],
                            "bundlestatus" => 'FAILED'
                    ]);
                    echo "Marked {$currentBundle['Name']} (v{$currentBundle['Version']}) as FAILED\n";
                }

                $statusResult = RabbitClient::getConnection()->send_request([
                        "type" => "updatebundlestatus",
                        "bundlename" => $selectedBundle['Name'],
                        "bundletype" => $type,
                        "bundleversion" => $selectedBundle['Version'],
                        "bundlestatus" => $environment === 'qa' ? 'DEPLOYED_QA' : 'DEPLOYED_PROD'
                ]);

                if (!$statusResult) {
                    echo "Warning: Unable to update bundle status\n";
                }
            } else {
                echo "Failed to deploy bundle\n";
            }

            break;
        case 'exit':
            exit();
        default:
            echo "Invalid type chosen\n";
            break;
    }

    echo "\n";
}
