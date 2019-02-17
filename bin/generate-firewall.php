<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LetsConnect\Common\CliParser;
use LetsConnect\Common\Config;
use LetsConnect\Common\FileIO;
use LetsConnect\Common\HttpClient\CurlHttpClient;
use LetsConnect\Common\HttpClient\ServerClient;
use LetsConnect\Node\Firewall;

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
    $firewallConfig = Config::fromFile(sprintf('%s/firewall.php', $configDir));
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
