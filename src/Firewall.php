<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LetsConnect\Node;

use LetsConnect\Common\Config;
use LetsConnect\Common\ProfileConfig;

class Firewall
{
    /**
     * @return string
     */
    public static function getFirewall4(array $profileList, Config $firewallConfig)
    {
        return implode(PHP_EOL, self::getArrayFirewall($profileList, $firewallConfig, 4)).PHP_EOL;
    }

    /**
     * @return string
     */
    public static function getFirewall6(array $profileList, Config $firewallConfig)
    {
        return implode(PHP_EOL, self::getArrayFirewall($profileList, $firewallConfig, 6)).PHP_EOL;
    }

    /**
     * @param int $inetFamily
     *
     * @return array
     */
    private static function getArrayFirewall(array $profileList, Config $firewallConfig, $inetFamily)
    {
        $firewall = [];

        // NAT
        $firewall = array_merge(
            $firewall,
             [
            '*nat',
            ':PREROUTING ACCEPT [0:0]',
            ':INPUT ACCEPT [0:0]',
            ':OUTPUT ACCEPT [0:0]',
            ':POSTROUTING ACCEPT [0:0]',
            ]
        );
        $firewall = array_merge($firewall, self::getNat($profileList, $inetFamily));
        $firewall[] = 'COMMIT';

        // FILTER
        $firewall = array_merge(
            $firewall,
            [
                '*filter',
                ':INPUT ACCEPT [0:0]',
                ':FORWARD ACCEPT [0:0]',
                ':OUTPUT ACCEPT [0:0]',
            ]
        );

        // INPUT
        $firewall = array_merge($firewall, self::getInputChain($inetFamily, $firewallConfig));

        // FORWARD
        $firewall = array_merge(
            $firewall,
            [
                sprintf('-A FORWARD -p %s -j ACCEPT', 4 === $inetFamily ? 'icmp' : 'ipv6-icmp'),
                '-A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT',
            ]
        );

        $firewall = array_merge($firewall, self::getForwardChain($profileList, $inetFamily));
        $firewall[] = sprintf('-A FORWARD -j REJECT --reject-with %s', 4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');
        $firewall[] = 'COMMIT';

        return array_merge(
            [
                '#',
                '# VPN Firewall Configuration',
                '#',
                '# ******************************************',
                '# * THIS FILE IS GENERATED, DO NOT MODIFY! *',
                '# ******************************************',
                '#',
            ],
            $firewall
        );
    }

    /**
     * @param int $inetFamily
     *
     * @return array
     */
    private static function getNat(array $profileList, $inetFamily)
    {
        $nat = [];

        foreach ($profileList as $profileId => $profileData) {
            $profileConfig = new ProfileConfig($profileData);

            $enableNat4 = $profileConfig->getItem('enableNat4');
            $enableNat6 = $profileConfig->getItem('enableNat6');

            if ($enableNat4 && 4 === $inetFamily) {
                $srcNet = $profileConfig->getItem('range');
                $nat[] = sprintf('-A POSTROUTING -s %s -o %s -j MASQUERADE', $srcNet, $profileConfig->getItem('extIf'));
            }

            if ($enableNat6 && 6 === $inetFamily) {
                $srcNet = $profileConfig->getItem('range6');
                $nat[] = sprintf('-A POSTROUTING -s %s -o %s -j MASQUERADE', $srcNet, $profileConfig->getItem('extIf'));
            }
        }

        return $nat;
    }

    /**
     * @param int $inetFamily
     *
     * @return array
     */
    private static function getInputChain($inetFamily, Config $firewallConfig)
    {
        $inputChain = [
            '-A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',
            sprintf('-A INPUT -p %s -j ACCEPT', 4 === $inetFamily ? 'icmp' : 'ipv6-icmp'),
            '-A INPUT -i lo -j ACCEPT',
        ];

        $udpPorts = $firewallConfig->getSection('inputChain')->getSection('udp')->toArray();
        $tcpPorts = $firewallConfig->getSection('inputChain')->getSection('tcp')->toArray();

        foreach ($udpPorts as $udpPort) {
            if (!\is_array($udpPort)) {
                $inputChain[] = sprintf(
                    '-A INPUT -m state --state NEW -m udp -p udp --dport %s -j ACCEPT',
                    $udpPort
                );

                continue;
            }

            foreach ($udpPort['src'] as $src) {
                $ipSource = new IP($src);
                if ($inetFamily === $ipSource->getFamily()) {
                    $inputChain[] = sprintf(
                        '-A INPUT -m state --state NEW -m udp -p udp --source %s --dport %s -j ACCEPT',
                        $src,
                        $udpPort['port']
                    );
                }
            }
        }

        foreach ($tcpPorts as $tcpPort) {
            if (!\is_array($tcpPort)) {
                $inputChain[] = sprintf(
                    '-A INPUT -m state --state NEW -m tcp -p tcp --dport %s -j ACCEPT',
                    $tcpPort
                );

                continue;
            }

            foreach ($tcpPort['src'] as $src) {
                $ipSource = new IP($src);
                if ($inetFamily === $ipSource->getFamily()) {
                    $inputChain[] = sprintf(
                        '-A INPUT -m state --state NEW -m tcp -p tcp --source %s --dport %s -j ACCEPT',
                        $src,
                        $tcpPort['port']
                    );
                }
            }
        }

        $inputChain[] = sprintf('-A INPUT -j REJECT --reject-with %s', 4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');

        return $inputChain;
    }

    /**
     * @param int $inetFamily
     *
     * @return array
     */
    private static function getForwardChain(array $profileList, $inetFamily)
    {
        $forwardChain = [];
        foreach ($profileList as $profileId => $profileData) {
            $profileConfig = new ProfileConfig($profileData);
            $profileNumber = $profileConfig->getItem('profileNumber');

            if (4 === $inetFamily && $profileConfig->getItem('reject4')) {
                // IPv4 forwarding is disabled
                continue;
            }

            if (6 === $inetFamily && $profileConfig->getItem('reject6')) {
                // IPv6 forwarding is disabled
                continue;
            }

            if (4 === $inetFamily) {
                // get the IPv4 range
                $srcNet = $profileConfig->getItem('range');
            } else {
                // get the IPv6 range
                $srcNet = $profileConfig->getItem('range6');
            }
            $forwardChain[] = sprintf('-N vpn-%s', $profileNumber);

            $forwardChain[] = sprintf('-A FORWARD -i tun-%s+ -s %s -j vpn-%s', $profileNumber, $srcNet, $profileNumber);

            if ($profileConfig->getItem('clientToClient')) {
                // allow client-to-client
                $forwardChain[] = sprintf('-A vpn-%s -o tun-%s+ -d %s -j ACCEPT', $profileNumber, $profileNumber, $srcNet);
            }
            if ($profileConfig->getItem('defaultGateway')) {
                // allow traffic to all outgoing destinations
                $forwardChain[] = sprintf('-A vpn-%s -o %s -j ACCEPT', $profileNumber, $profileConfig->getItem('extIf'));
            } else {
                // only allow certain traffic to the external interface
                foreach ($profileConfig->getSection('routes')->toArray() as $route) {
                    $routeIp = new IP($route);
                    if ($inetFamily === $routeIp->getFamily()) {
                        $forwardChain[] = sprintf('-A vpn-%s -o %s -d %s -j ACCEPT', $profileNumber, $profileConfig->getItem('extIf'), $route);
                    }
                }
            }
        }

        return $forwardChain;
    }
}
