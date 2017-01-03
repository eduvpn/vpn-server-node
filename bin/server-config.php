#!/usr/bin/env php
<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

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
            'profile' => ['the profile identifier', true, true],
            'generate' => ['generate a new certificate for the server', false, false],
        ]
    );

    $opt = $p->parse($argv);
    if ($opt->hasItem('help')) {
        echo $p->help();
        exit(0);
    }

    $instanceId = $opt->hasItem('instance') ? $opt->getItem('instance') : 'default';

    $profileId = $opt->getItem('profile');
    $generateCerts = $opt->hasItem('generate');

    $configFile = sprintf('%s/config/%s/config.php', dirname(__DIR__), $instanceId);
    $config = Config::fromFile($configFile);

    $vpnUser = $config->hasItem('vpnUser') ? $config->getItem('vpnUser') : 'openvpn';
    $vpnGroup = $config->hasItem('vpnGroup') ? $config->getItem('vpnGroup') : 'openvpn';

    $vpnConfigDir = sprintf('%s/openvpn-config', dirname(__DIR__));
    $vpnTlsDir = sprintf('%s/openvpn-config/tls/%s/%s', dirname(__DIR__), $instanceId, $profileId);

    $serverClient = new ServerClient(
        new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
        $config->getItem('apiUri')
    );

    $instanceNumber = $serverClient->get('instance_number');
    $profileList = $serverClient->get('profile_list');
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
        $dhSourceFile = sprintf('%s/config/dh.pem', dirname(__DIR__));
        $o->generateKeys($serverClient, $cn, $dhSourceFile);
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
