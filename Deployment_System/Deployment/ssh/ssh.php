#!/usr/bin/php
<?php
$iniPath = __DIR__ . '/hosts.ini';
if (!file_exists($iniPath)) {
    echo "Config file not found: $iniPath\n";
    exit(1);
}

$config = parse_ini_file($iniPath, true);
$sshKeyPath = getenv('HOME') . '/.ssh/id_rsa';

if (!file_exists($sshKeyPath)) {
    exec("ssh-keygen -f " . escapeshellarg($sshKeyPath) . " -N ''");
    echo "Key generated\n\n";
}

foreach ($config as $server) {
    if (empty($server['HOST']) || empty($server['USER'])) continue;
    $key = $server['USER'] . '@' . $server['HOST'];
    echo "Copying key to $key\n";
    passthru("ssh-copy-id -i " . escapeshellarg($sshKeyPath . ".pub") . ' ' . escapeshellarg($key));
    echo "\n";
}

exit();