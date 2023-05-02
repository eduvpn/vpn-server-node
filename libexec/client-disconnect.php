<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2023, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use Vpn\Node\Config;
use Vpn\Node\Connection;
use Vpn\Node\FileIO;
use Vpn\Node\HttpClient\CurlHttpClient;
use Vpn\Node\Syslog;
use Vpn\Node\Utils;

try {
    $nodeKeyFile = $baseDir.'/config/keys/node.key';
    $config = Config::fromFile($baseDir.'/config/config.php');
    $connection = new Connection(new CurlHttpClient(), $config->apiUrl(), $config->nodeNumber(), FileIO::read($nodeKeyFile));
    $connection->disconnect(
        Utils::reqEnvString('PROFILE_ID'),
        Utils::reqEnvString('common_name'),
        Utils::optEnvString('trusted_ip'),
        Utils::optEnvString('trusted_ip6'),
        Utils::reqEnvString('ifconfig_pool_remote_ip'),
        Utils::reqEnvString('ifconfig_pool_remote_ip6'),
        Utils::reqEnvString('bytes_received'),
        Utils::reqEnvString('bytes_sent'),
    );
} catch (Exception $e) {
    $log = new Syslog('vpn-server-node');
    $log->error('DISCONNECT: '.$e->getMessage());

    exit(1);
}
