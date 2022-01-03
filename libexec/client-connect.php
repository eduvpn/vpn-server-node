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
use Vpn\Node\Connection;
use Vpn\Node\HttpClient\CurlHttpClient;
use Vpn\Node\Syslog;
use Vpn\Node\Utils;

try {
    $config = Config::fromFile($baseDir.'/config/config.php');
    $connection = new Connection(new CurlHttpClient(), $config->apiUrl(), $config->nodeNumber(), Utils::readFile($baseDir.'/config/node.key'));
    $connection->connect(
        Utils::reqEnvString('PROFILE_ID'),
        Utils::reqEnvString('X509_0_OU'),
        Utils::reqEnvString('common_name'),
        Utils::optEnvString('trusted_ip'),
        Utils::optEnvString('trusted_ip6'),
        Utils::reqEnvString('ifconfig_pool_remote_ip'),
        Utils::reqEnvString('ifconfig_pool_remote_ip6'),
        Utils::reqEnvString('time_unix')
    );
} catch (Exception $e) {
    $log = new Syslog('client-connect');
    $log->error($e->getMessage());

    exit(1);
}
