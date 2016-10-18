#!/usr/bin/php
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

use SURFnet\VPN\Node\Firewall;
use SURFnet\VPN\Node\FirewallConfig;
use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\CliParser;
use SURFnet\VPN\Common\HttpClient\GuzzleHttpClient;
use SURFnet\VPN\Common\HttpClient\ServerClient;

try {
    $p = new CliParser(
        'Generate firewall rules for all instances',
        [
            'install' => ['install the firewall', false, false],
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
    $configList = [];
    foreach (glob(sprintf('%s/*', $configDir), GLOB_ONLYDIR | GLOB_ERR) as $instanceDir) {
        $instanceId = basename($instanceDir);
        $config = Config::fromFile(sprintf('%s/%s/config.yaml', $configDir, $instanceId));

        $serverClient = new ServerClient(
            new GuzzleHttpClient(
                [
                    'defaults' => [
                        'auth' => [
                            $config->v('apiProviders', 'vpn-server-api', 'userName'),
                            $config->v('apiProviders', 'vpn-server-api', 'userPass'),
                        ],
                    ],
                ]
            ),
            $config->v('apiProviders', 'vpn-server-api', 'apiUri')
        );

        $instanceConfig = $serverClient->instanceConfig();
        $configList[$instanceId] = new Config($instanceConfig);
    }

    $firewall = Firewall::getFirewall4($configList, $firewallConfig);
    $firewall6 = Firewall::getFirewall6($configList, $firewallConfig);

    if ($opt->e('install')) {
        FileIO::writeFile('/etc/sysconfig/iptables', $firewall, 0600);
        FileIO::writeFile('/etc/sysconfig/ip6tables', $firewall6, 0600);
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
