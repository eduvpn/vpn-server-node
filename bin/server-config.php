<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LC\Common\Config;
use LC\Common\HttpClient\CurlHttpClient;
use LC\Common\HttpClient\ServerClient;
use LC\Node\OpenVpn;

try {
    $configFile = sprintf('%s/config/config.php', $baseDir);
    $config = Config::fromFile($configFile);

    $vpnUser = $config->hasItem('vpnUser') ? $config->getItem('vpnUser') : 'openvpn';
    $vpnGroup = $config->hasItem('vpnGroup') ? $config->getItem('vpnGroup') : 'openvpn';

    $vpnConfigDir = sprintf('%s/openvpn-config', $baseDir);
    $serverClient = new ServerClient(
        new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
        $config->getItem('apiUri')
    );

    $o = new OpenVpn($vpnConfigDir);
    $o->writeProfiles($serverClient, $vpnUser, $vpnGroup);
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
