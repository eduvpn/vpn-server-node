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

use SURFnet\VPN\Common\CliParser;
use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\HttpClient\CurlHttpClient;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Firewall;
use SURFnet\VPN\Node\FirewallConfig;

try {
    $p = new CliParser(
        'Generate firewall rules for all instances',
        [
            'install' => ['install the firewall', false, false],
        ]
    );

    $opt = $p->parse($argv);
    if ($opt->hasItem('help')) {
        echo $p->help();
        exit(0);
    }

    $configDir = sprintf('%s/config', $baseDir);

    // load generic firewall configuration
    try {
        $firewallConfig = FirewallConfig::fromFile(sprintf('%s/firewall.php', $configDir));
    } catch (RuntimeException $e) {
        $firewallConfig = new FirewallConfig([]);
    }

    $config = Config::fromFile(sprintf('%s/config.php', $configDir));

    $serverClient = new ServerClient(
        new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
        $config->getItem('apiUri')
    );
    $profileList = $serverClient->getRequireArray('profile_list');
    $firewall = Firewall::getFirewall4($profileList, $firewallConfig);
    $firewall6 = Firewall::getFirewall6($profileList, $firewallConfig);

    if ($opt->hasItem('install')) {
        // determine file location for writing firewall data
        if (FileIO::exists('/etc/redhat-release')) {
            // RHEL/CentOS/Fedora
            echo 'OS Detected: RHEL/CentOS/Fedora...'.PHP_EOL;
            $iptablesFile = '/etc/sysconfig/iptables';
            $ip6tablesFile = '/etc/sysconfig/ip6tables';
        } elseif (FileIO::exists('/etc/debian_version')) {
            // Debian/Ubuntu
            echo 'OS Detected: Debian/Ubuntu...'.PHP_EOL;
            $iptablesFile = '/etc/iptables/rules.v4';
            $ip6tablesFile = '/etc/iptables/rules.v6';
        } else {
            throw new Exception('only RHEL/CentOS/Fedora or Debian/Ubuntu supported');
        }

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
