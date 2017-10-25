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
            'debian' => ['install the firewall for Debian iptables-persistent', false, false],
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

    // detect all instances
    $instanceList = $firewallConfig->getSection('instanceList')->toArray();

    $configList = [];
    foreach ($instanceList as $instanceId) {
        $config = Config::fromFile(sprintf('%s/%s/config.php', $configDir, $instanceId));

        $serverClient = new ServerClient(
            new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
            $config->getItem('apiUri')
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
    if ($opt->hasItem('debian')) {
        $iptablesFile = '/etc/iptables/rules.v4';
        $ip6tablesFile = '/etc/iptables/rules.v6';
    }

    if ($opt->hasItem('install')) {
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
