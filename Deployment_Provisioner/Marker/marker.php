#!/usr/bin/php
<?php
require_once('../RabbitMQ/RabbitClient.php');

while (true) {
    $type = strtolower(readline("Type? (WEBSERVER/BROKER/DATASOURCE/EXIT): "));
    echo "\n";

    switch ($type) {
        case 'datasource':
        case 'broker':
        case 'webserver':
            $bundles = RabbitClient::getConnection()->send_request([
                    "type" => "getbundlesbytypeandstatus",
                    "bundlestatus" => "DEPLOYED_QA",
                    "bundletype" => $type
            ]);

            if (!is_array($bundles) || empty($bundles)) {
                echo "No deployed QA bundles found\n";
                break;
            }

            echo "Bundles:\n";
            foreach ($bundles as $i => $bundle) {
                echo "  [$i] {$bundle['Name']} | v{$bundle['Version']}\n";
            }

            echo "\n";
            $bundleIndex = readline("Choose bundle index: ");

            if (!is_numeric($bundleIndex) || $bundleIndex < 0 || $bundleIndex >= count($bundles)) {
                echo "Invalid bundle chosen\n";
                break;
            }

            echo "\n";
            $selectedBundle = $bundles[$bundleIndex];

            echo "Selected: {$selectedBundle['Name']} (v{$selectedBundle['Version']})\n";
            $status = strtoupper(readline("Status? (PASSED/FAILED): "));
            echo "\n";

            if ($status !== 'PASSED' && $status !== 'FAILED') {
                echo "Invalid status chosen\n";
                break;
            }

            $confirm = strtolower(readline("Confirm status {$selectedBundle['Name']} (v${selectedBundle['Version']}) as $status? (Y/N): "));
            echo "\n";

            if ($confirm !== 'y') {
                echo "Cancelled\n";
                break;
            }

            $result = RabbitClient::getConnection()->send_request([
                    "type" => "updatebundlestatus",
                    "bundlename" => $selectedBundle['Name'],
                    "bundletype" => $type,
                    "bundleversion" => $selectedBundle['Version'],
                    "bundlestatus" => $status
            ]);

            if ($result) {
                echo "Successfully changed {$selectedBundle['Name']} (v{$selectedBundle['Version']}) to $status\n";
            } else {
                echo "Failed to update bundle status\n";
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