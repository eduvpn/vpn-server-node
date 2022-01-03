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

use Vpn\Node\KeyPair;
use Vpn\Node\Utils;

// allow group to read the created files/folders
umask(0027);

try {
    $wgKeyFile = $baseDir.'/config/wireguard.key';
    if (!Utils::fileExists($wgKeyFile)) {
        $keyPair = KeyPair::generate();
        Utils::writeFile($wgKeyFile, $keyPair['secret_key']);
    }
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().\PHP_EOL;

    exit(1);
}
