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

namespace SURFnet\VPN\Node;

use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\ProfileConfig;

class Firewall
{
    public static function getFirewall4(array $configList, FirewallConfig $firewallConfig, $asArray = false)
    {
        return self::getFirewall($configList, $firewallConfig, 4, $asArray);
    }

    public static function getFirewall6(array $configList, FirewallConfig $firewallConfig, $asArray = false)
    {
        return self::getFirewall($configList, $firewallConfig, 6, $asArray);
    }

    private static function getFirewall(array $configList, FirewallConfig $firewallConfig, $inetFamily, $asArray)
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
        // add all instances
        foreach ($configList as $config) {
            $firewall = array_merge($firewall, self::getNat($config, $inetFamily));
        }
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
                '-A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT',
            ]
        );

        // add all instances
        foreach ($configList as $config) {
            $firewall = array_merge($firewall, self::getForwardChain($config, $inetFamily));
        }
        $firewall[] = sprintf('-A FORWARD -j REJECT --reject-with %s', 4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');
        $firewall[] = 'COMMIT';

        if ($asArray) {
            return $firewall;
        }

        return implode(PHP_EOL, $firewall).PHP_EOL;
    }

    private static function getNat(Config $config, $inetFamily)
    {
        $nat = [];

        foreach (array_keys($config->v('vpnProfiles')) as $profileId) {
            $profileConfig = new ProfileConfig($config->v('vpnProfiles', $profileId));
            if ($profileConfig->v('useNat')) {
                if (4 === $inetFamily) {
                    // get the IPv4 range
                    $srcNet = $profileConfig->v('range');
                } else {
                    // get the IPv6 range
                    $srcNet = $profileConfig->v('range6');
                }
                // -i (--in-interface) cannot be specified for POSTROUTING
                $nat[] = sprintf('-A POSTROUTING -s %s -o %s -j MASQUERADE', $srcNet, $profileConfig->v('extIf'));
            }
        }

        return $nat;
    }

    private static function getInputChain($inetFamily, FirewallConfig $firewallConfig)
    {
        $inputChain = [
            '-A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',
            sprintf('-A INPUT -p %s -j ACCEPT', 4 === $inetFamily ? 'icmp' : 'ipv6-icmp'),
            '-A INPUT -i lo -j ACCEPT',
        ];

        // add trusted interfaces
        if ($firewallConfig->e('inputChain', 'trustedInterfaces')) {
            foreach ($firewallConfig->v('inputChain', 'trustedInterfaces') as $trustedIf) {
                $inputChain[] = sprintf('-A INPUT -i %s -j ACCEPT', $trustedIf);
            }
        }

        // NOTE: multiport is limited to 15 ports (a range counts as two)
        $inputChain[] = sprintf(
            '-A INPUT -m state --state NEW -m multiport -p udp --dports %s -j ACCEPT',
            implode(',', $firewallConfig->v('inputChain', 'udp'))
        );

        $inputChain[] = sprintf(
            '-A INPUT -m state --state NEW -m multiport -p tcp --dports %s -j ACCEPT',
            implode(',', $firewallConfig->v('inputChain', 'tcp'))
        );

        $inputChain[] = sprintf('-A INPUT -j REJECT --reject-with %s', 4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');

        return $inputChain;
    }

    private static function getForwardChain(Config $config, $inetFamily)
    {
        $forwardChain = [];

        foreach (array_keys($config->v('vpnProfiles')) as $profileId) {
            $profileConfig = new Config($config->v('vpnProfiles', $profileId));
            $profileNumber = $profileConfig->v('profileNumber');

            if (4 === $inetFamily && $profileConfig->v('reject4')) {
                // IPv4 forwarding is disabled
                continue;
            }

            if (6 === $inetFamily && $profileConfig->v('reject6')) {
                // IPv6 forwarding is disabled
                continue;
            }

            if (4 === $inetFamily) {
                // get the IPv4 range
                $srcNet = $profileConfig->v('range');
            } else {
                // get the IPv6 range
                $srcNet = $profileConfig->v('range6');
            }
            $forwardChain[] = sprintf('-N vpn-%s-%s', $config->v('instanceNumber'), $profileNumber);

            $forwardChain[] = sprintf('-A FORWARD -i tun-%s-%s+ -s %s -j vpn-%s-%s', $config->v('instanceNumber'), $profileNumber, $srcNet, $config->v('instanceNumber'), $profileNumber);

            // merge outgoing forwarding firewall rules to prevent certain
            // traffic
            $forwardChain = array_merge($forwardChain, self::getForwardFirewall($config->v('instanceNumber'), $profileNumber, $profileConfig, $inetFamily));

            if ($profileConfig->v('clientToClient')) {
                // allow client-to-client
                $forwardChain[] = sprintf('-A vpn-%s-%s -o tun-%s-%s+ -d %s -j ACCEPT', $config->v('instanceNumber'), $profileNumber, $config->v('instanceNumber'), $profileNumber, $srcNet);
            }
            if ($profileConfig->v('defaultGateway')) {
                // allow traffic to all outgoing destinations
                $forwardChain[] = sprintf('-A vpn-%s-%s -o %s -j ACCEPT', $config->v('instanceNumber'), $profileNumber, $profileConfig->v('extIf'), $srcNet);
            } else {
                // only allow certain traffic to the external interface
                foreach ($profileConfig->v('routes') as $route) {
                    $routeIp = new IP($route);
                    if ($inetFamily === $routeIp->getFamily()) {
                        $forwardChain[] = sprintf('-A vpn-%s-%s -o %s -d %s -j ACCEPT', $config->v('instanceNumber'), $profileNumber, $profileConfig->v('extIf'), $route);
                    }
                }
            }
        }

        return $forwardChain;
    }

    private static function getForwardFirewall($instanceNumber, $profileNumber, Config $profileConfig, $inetFamily)
    {
        $forwardFirewall = [];
        if ($profileConfig->v('blockSmb')) {
            // drop SMB outgoing traffic
            // @see https://medium.com/@ValdikSS/deanonymizing-windows-users-and-capturing-microsoft-and-vpn-accounts-f7e53fe73834
            foreach (['tcp', 'udp'] as $proto) {
                $forwardFirewall[] = sprintf(
                    '-A vpn-%s-%s -o %s -m multiport -p %s --dports 137:139,445 -j REJECT --reject-with %s',
                    $instanceNumber,
                    $profileNumber,
                    $profileConfig->v('extIf'),
                    $proto,
                    4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');
            }
        }

        return $forwardFirewall;
    }
}
