#!/usr/bin/env php
<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LetsConnect\Common\Config;
use LetsConnect\Common\HttpClient\CurlHttpClient;
use LetsConnect\Common\HttpClient\ServerClient;
use LetsConnect\Node\OpenVpn;

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
