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
use LC\Node\Utils;

try {
    $openVpnConfigDir = sprintf('%s/openvpn-config', $baseDir);
    $wgConfigDir = sprintf('%s/wg-config', $baseDir);
    $configDir = sprintf('%s/config', $baseDir);
    $config = Config::fromFile($configDir.'/config.php');
    $apiSecretFile = $configDir.'/node.key';
    $apiSecret = Utils::readFile($apiSecretFile);
    $httpClient = new CurlHttpClient();
    $httpClient->setRequestHeader('Authorization', 'Bearer '.$apiSecret);
    $configWriter = new ConfigWriter($openVpnConfigDir, $wgConfigDir, $httpClient, $config->apiUrl(), $config->nodeNumber());
    $configWriter->write();
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().\PHP_EOL;

    exit(1);
}
