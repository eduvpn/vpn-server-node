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
use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\HttpClient\GuzzleHttpClient;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Firewall;
use SURFnet\VPN\Node\FirewallConfig;

try {
    $p = new CliParser(
        'Generate firewall rules for all instances',
        [
            'install' => ['install the firewall', false, false],
            'debian' => ['install the firewall for Debian iptables-persistent', false, false],
        ]
    );

    $opt = $p->parse($argv);
    if ($opt->e('help')) {
        echo $p->help();
        exit(0);
    }

    $configDir = sprintf('%s/config', dirname(__DIR__));

    // load generic firewall configuration
    try {
        $firewallConfig = FirewallConfig::fromFile(sprintf('%s/firewall.yaml', $configDir));
    } catch (RuntimeException $e) {
        $firewallConfig = new FirewallConfig([]);
    }

    // detect all instances
    $instanceList = $firewallConfig->v('instanceList');

    $configList = [];
    foreach ($instanceList as $instanceId) {
        $config = Config::fromFile(sprintf('%s/%s/config.yaml', $configDir, $instanceId));

        $serverClient = new ServerClient(
            new GuzzleHttpClient(
                [
                    'defaults' => [
                        'auth' => [
                            $config->v('apiUser'),
                            $config->v('apiPass'),
                        ],
                    ],
                ]
            ),
            $config->v('apiUri')
        );

        $instanceNumber = $serverClient->get('instance_number');
        $profileList = $serverClient->get('profile_list');

        $configList[] = ['instanceNumber' => $instanceNumber, 'profileList' => $profileList];
    }

    $firewall = Firewall::getFirewall4($configList, $firewallConfig);
    $firewall6 = Firewall::getFirewall6($configList, $firewallConfig);

    // determine file location for writing firewall data
    $iptablesFile = '/etc/sysconfig/iptables';
    $ip6tablesFile = '/etc/sysconfig/ip6tables';
    if ($opt->e('debian')) {
        $iptablesFile = '/etc/iptables/rules.v4';
        $ip6tablesFile = '/etc/iptables/rules.v6';
    }

    if ($opt->e('install')) {
        FileIO::writeFile($iptablesFile, $firewall, 0600);
        FileIO::writeFile($ip6tablesFile, $firewall6, 0600);
    } else {
        echo '##########################################'.PHP_EOL;
        echo '# IPv4'.PHP_EOL;
        echo '##########################################'.PHP_EOL;
        echo $firewall;

        echo '##########################################'.PHP_EOL;
        echo '# IPv6'.PHP_EOL;
        echo '##########################################'.PHP_EOL;
        echo $firewall6;
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
