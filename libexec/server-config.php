<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2022, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use Vpn\Node\Config;
use Vpn\Node\ConfigWriter;
use Vpn\Node\FileIO;
use Vpn\Node\HttpClient\CurlHttpClient;

try {
    $nodeKeyFile = $baseDir.'/config/keys/node.key';
    $config = Config::fromFile($baseDir.'/config/config.php');
    $configWriter = new ConfigWriter($baseDir, new CurlHttpClient(), $config, FileIO::read($nodeKeyFile));
    $configWriter->write();
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().\PHP_EOL;

    exit(1);
}
