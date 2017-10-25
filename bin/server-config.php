#!/usr/bin/env php
<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */
$baseDir = dirname(__DIR__);

// find the autoloader (package installs, composer)
foreach (['src', 'vendor'] as $autoloadDir) {
    if (@file_exists(sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir))) {
        require_once sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir);
        break;
    }
}
use SURFnet\VPN\Common\CliParser;
use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\HttpClient\CurlHttpClient;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\ProfileConfig;
use SURFnet\VPN\Node\OpenVpn;

try {
    $p = new CliParser(
        'Generate VPN server configuration for an instance',
        [
            'instance' => ['the VPN instance', true, false],
            'generate' => ['generate a new certificate for the server', false, false],
        ]
    );

    $opt = $p->parse($argv);
    if ($opt->hasItem('help')) {
        echo $p->help();
        exit(0);
    }

    $instanceId = $opt->hasItem('instance') ? $opt->getItem('instance') : 'default';
    $generateCerts = $opt->hasItem('generate');

    $configFile = sprintf('%s/config/%s/config.php', $baseDir, $instanceId);
    $config = Config::fromFile($configFile);

    $vpnUser = $config->hasItem('vpnUser') ? $config->getItem('vpnUser') : 'openvpn';
    $vpnGroup = $config->hasItem('vpnGroup') ? $config->getItem('vpnGroup') : 'openvpn';

    $vpnConfigDir = sprintf('%s/openvpn-config', $baseDir);
    $serverClient = new ServerClient(
        new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
        $config->getItem('apiUri')
    );

    $instanceNumber = $serverClient->get('instance_number');
    $profileList = $serverClient->get('profile_list');

    $profileIdList = array_keys($profileList);
    foreach ($profileIdList as $profileId) {
        $vpnTlsDir = sprintf('%s/openvpn-config/tls/%s/%s', $baseDir, $instanceId, $profileId);
        $profileConfigData = $profileList[$profileId];
        $profileConfigData['_user'] = $vpnUser;
        $profileConfigData['_group'] = $vpnGroup;
        $profileConfig = new ProfileConfig($profileConfigData);

        $o = new OpenVpn($vpnConfigDir, $vpnTlsDir);
        $o->writeProfile($instanceNumber, $instanceId, $profileId, $profileConfig);
        if ($generateCerts) {
            // generate a CN based on date and profile, instance
            $dateTime = new DateTime('now', new DateTimeZone('UTC'));
            $dateString = $dateTime->format('YmdHis');
            $cn = sprintf('%s.%s.%s', $dateString, $profileId, $instanceId);
            $o->generateKeys($serverClient, $cn);
        }
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
