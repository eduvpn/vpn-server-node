<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LC\Node\Config;
use LC\Node\Connection;
use LC\Node\HttpClient\CurlHttpClient;
use LC\Node\Logger;

$logger = new Logger(
    basename($argv[0])
);

try {
    $envData = [];
    $envKeys = [
        'PROFILE_ID',
        'common_name',
        'time_unix',
        'ifconfig_pool_remote_ip',
        'ifconfig_pool_remote_ip6',
        'bytes_received',
        'bytes_sent',
        'time_duration',
    ];

    // read environment variables
    foreach ($envKeys as $envKey) {
        $envData[$envKey] = getenv($envKey);
    }

    $configDir = sprintf('%s/config', $baseDir);
    $config = Config::fromFile($configDir.'/config.php');
    $apiSecretFile = $configDir.'/node.key';
    if (false === $apiSecret = file_get_contents($apiSecretFile)) {
        throw new RuntimeException('unable to read "'.$apiSecretFile.'"');
    }
    $httpClient = new CurlHttpClient($apiSecret);
    $apiUrl = $config->requireString('apiUrl');

    $connection = new Connection($httpClient, $apiUrl);
    $connection->disconnect($envData);
} catch (Exception $e) {
    $logger->error($e->getMessage());
    exit(1);
}
