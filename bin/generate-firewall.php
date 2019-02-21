<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use LetsConnect\Common\Config;
use LetsConnect\Common\FileIO;
use LetsConnect\Common\HttpClient\CurlHttpClient;
use LetsConnect\Common\HttpClient\ServerClient;
use LetsConnect\Common\ProfileConfig;
use LetsConnect\Node\Firewall;

try {
    $installFirewall = false;
    foreach ($argv as $arg) {
        if ('--install' === $arg) {
            $installFirewall = true;
        }
    }
    $configDir = sprintf('%s/config', $baseDir);
    $mainConfig = Config::fromFile(sprintf('%s/config.php', $configDir));
    $firewallConfig = Config::fromFile(sprintf('%s/firewall.php', $configDir));

    $serverClient = new ServerClient(
        new CurlHttpClient(
            [
                $mainConfig->getItem('apiUser'),
                $mainConfig->getItem('apiPass'),
            ]
        ),
        $mainConfig->getItem('apiUri')
    );

    $profileList = $serverClient->getRequireArray('profile_list');
    /** @var array<string,LetsConnect\Common\ProfileConfig> */
    $profileConfigList = [];
    foreach ($profileList as $profileId => $profileData) {
        $profileConfigList[$profileId] = new ProfileConfig($profileData);
    }

    $firewallIp4 = new Firewall(4);
    $firewallIp6 = new Firewall(6);
    if ($installFirewall) {
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
            throw new RuntimeException('only RHEL/CentOS/Fedora or Debian/Ubuntu supported');
        }

        FileIO::writeFile($iptablesFile, $firewallIp4->get($firewallConfig, $profileConfigList), 0600);
        FileIO::writeFile($ip6tablesFile, $firewallIp6->get($firewallConfig, $profileConfigList), 0600);
    } else {
        echo '##########################################'.PHP_EOL;
        echo '# IPv4'.PHP_EOL;
        echo '##########################################'.PHP_EOL;
        echo $firewallIp4->get($firewallConfig, $profileConfigList);

        echo '##########################################'.PHP_EOL;
        echo '# IPv6'.PHP_EOL;
        echo '##########################################'.PHP_EOL;
        echo $firewallIp6->get($firewallConfig, $profileConfigList);
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
