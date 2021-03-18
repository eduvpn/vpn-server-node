<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LC\Common\Config;
use LC\Common\FileIO;
use LC\Common\HttpClient\CurlHttpClient;
use LC\Common\HttpClient\ServerClient;
use LC\Common\Logger;
use LC\Node\Connection;
use LC\Node\Exception\ConnectionException;

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
    $config = Config::fromFile(
        sprintf('%s/config.php', $configDir)
    );

    $serverClient = new ServerClient(
        new CurlHttpClient('vpn-server-node', trim(FileIO::readFile($configDir.'/node.key'))),
        $config->requireString('apiUri')
    );

    $connection = new Connection($serverClient);
    $connection->disconnect($envData);
} catch (ConnectionException $e) {
    $logger->info($e->getMessage(), $e->getEnvData());
    exit(1);
} catch (Exception $e) {
    $logger->error($e->getMessage());
    exit(1);
}
