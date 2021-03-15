<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LC\Common\Config;
use LC\Common\HttpClient\CurlHttpClient;
use LC\Common\HttpClient\Exception\ApiException;
use LC\Common\HttpClient\ServerClient;
use LC\Common\Logger;
use LC\Node\Connection;
use LC\Common\FileIO;

$logger = new Logger(
    basename($argv[0])
);

$envData = [];
try {
    $envKeys = [
        'PROFILE_ID',
        'common_name',
        'time_unix',
        'ifconfig_pool_remote_ip',
        'ifconfig_pool_remote_ip6',
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
    $connection->connect($envData);
} catch (ApiException $e) {
    $logger->warning($e->getMessage());
    exit(1);
} catch (Exception $e) {
    $logger->error($e->getMessage());
    exit(1);
}
