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
use LC\Node\ConfigWriter;
use LC\Node\HttpClient\CurlHttpClient;

try {
    $vpnConfigDir = sprintf('%s/openvpn-config', $baseDir);
    $configDir = sprintf('%s/config', $baseDir);
    $config = Config::fromFile($configDir.'/config.php');
    $apiSecretFile = $configDir.'/node.key';
    if (false === $apiSecret = file_get_contents($apiSecretFile)) {
        throw new RuntimeException('unable to read "'.$apiSecretFile.'"');
    }
    $httpClient = new CurlHttpClient($apiSecret);
    $apiUrl = $config->requireString('apiUrl');
    $configWriter = new ConfigWriter($vpnConfigDir, $httpClient, $apiUrl);
    $configWriter->write($config->requireArray('profileList', []));
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).\PHP_EOL;
    exit(1);
}
