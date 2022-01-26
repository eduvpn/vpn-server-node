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

use Vpn\Node\FileIO;
use Vpn\Node\KeyPair;

// allow group to read the created files/folders
umask(0027);

try {
    $keyDir = $baseDir.'/config/keys';
    FileIO::mkdir($keyDir);

    $wgKeyFile = $keyDir.'/wireguard.key';
    if (!FileIO::exists($wgKeyFile)) {
        $keyPair = KeyPair::generate();
        FileIO::write($wgKeyFile, $keyPair['secret_key']);
    }
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().\PHP_EOL;

    exit(1);
}
