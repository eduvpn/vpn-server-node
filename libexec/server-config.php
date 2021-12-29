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

use Vpn\Node\Config;
use Vpn\Node\ConfigWriter;
use Vpn\Node\HttpClient\CurlHttpClient;
use Vpn\Node\Utils;

try {
    $config = Config::fromFile($baseDir.'/config/config.php');
    $httpClient = new CurlHttpClient();
    $httpClient->setRequestHeader('X-Node-Number', (string) $config->nodeNumber());
    $httpClient->setRequestHeader('Authorization', 'Bearer '.Utils::readFile($baseDir.'/config/node.key'));
    $configWriter = new ConfigWriter($baseDir, $httpClient, $config);
    $configWriter->write();
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().\PHP_EOL;

    exit(1);
}
