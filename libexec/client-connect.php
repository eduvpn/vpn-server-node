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
use LC\Node\Syslog;
use RuntimeException;

function envString(string $envKey): string
{
    if (false === $envValue = getenv($envKey)) {
        throw new RuntimeException('environment variable "'.$envKey.'" not set');
    }

    return $envValue;
}

try {
    $configFile = $baseDir.'/config/config.php';
    $config = Config::fromFile($configFile);
    $apiSecretFile = $baseDir.'/config/node.key';
    if (false === $apiSecret = file_get_contents($apiSecretFile)) {
        throw new RuntimeException('unable to read file "'.$apiSecretFile.'"');
    }
    $httpClient = new CurlHttpClient($apiSecret);
    $apiUrl = $config->requireString('apiUrl');
    $connection = new Connection($httpClient, $apiUrl);
    $connection->connect(
        envString('PROFILE_ID'),
        envString('common_name'),
        envString('ifconfig_pool_remote_ip'),
        envString('ifconfig_pool_remote_ip6'),
        envString('time_unix')
    );
} catch (Exception $e) {
    $log = new Syslog('client-connect');
    $log->error($e->getMessage());
    exit(1);
}
