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
use Vpn\Node\HttpClient\Exception\HttpClientException;

// allow group to read the created files/folders
umask(0027);

try {
    $nodeKeyFile = $baseDir.'/config/keys/node.key';
    $config = Config::fromFile($baseDir.'/config/config.php');
    $configWriter = new ConfigWriter($baseDir, new CurlHttpClient(), $config, FileIO::read($nodeKeyFile));
    $configWriter->write();
} catch (Exception $e) {
    $exceptionMessage = $e->getMessage();
    if ($e instanceof HttpClientException) {
        // add the HttpClientResponse body to it
        $exceptionMessage = (string) $e->httpClientResponse();
    }

    echo 'ERROR: '.$exceptionMessage.\PHP_EOL;

    exit(1);
}
