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
        $firewall = array_merge($firewall, self::getNat($firewallConfig, $profileList, $inetFamily));
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
    private static function getNat(Config $firewallConfig, array $profileList, $inetFamily)
    {
        $natRules = [];

        if (!$firewallConfig->hasSection('natConfig')) {
            return $natRules;
        }

        // loop over all profiles
        $natProfileList = $firewallConfig->getSection('natConfig')->optionalItem('profileList', []);
        foreach ($natProfileList as $profileId) {
            if (!array_key_exists($profileId, $profileList)) {
                continue;
            }
            $profileConfig = new ProfileConfig($profileList[$profileId]);
            $srcNet = 4 === $inetFamily ? $profileConfig->getItem('range') : $profileConfig->getItem('range6');
            $natRules[] = sprintf(
                '-A POSTROUTING -s %s -o %s -j MASQUERADE',
                $srcNet,
                $firewallConfig->getSection('natConfig')->getItem('extIf')
            );
        }

        return $natRules;
    }

    /**
     * @param int $inetFamily
     *
     * @return array
     */
    private static function getInputChain($inetFamily, Config $firewallConfig)
    {
        $inputChain = [
            '-A INPUT -i lo -j ACCEPT',
            sprintf('-A INPUT -p %s -j ACCEPT', 4 === $inetFamily ? 'icmp' : 'ipv6-icmp'),
            '-A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',
        ];

        $inputRules = $firewallConfig->getSection('inputRules')->toArray();
        foreach ($inputRules as $inputRule) {
            $protoList = $inputRule['proto'];
            $srcNetList = array_key_exists('src_net', $inputRule) ? $inputRule['src_net'] : [];
            $dstPortList = $inputRule['dst_port'];

            foreach ($protoList as $proto) {
                foreach ($dstPortList as $dstPort) {
                    if (0 === \count($srcNetList)) {
                        $inputChain[] = sprintf(
                            '-A INPUT -m state --state NEW -m %s -p %s --dport %s -j ACCEPT',
                            $proto,
                            $proto,
                            $dstPort
                        );
                        continue;
                    }
                    foreach ($srcNetList as $srcNet) {
                        $srcNetFamily = false === strpos($srcNet, ':') ? 4 : 6;
                        if ($srcNetFamily === $inetFamily) {
                            $inputChain[] = sprintf(
                                '-A INPUT -m state --state NEW -m %s -p %s --source %s --dport %s -j ACCEPT',
                                $proto,
                                $proto,
                                $srcNet,
                                $dstPort
                            );
                        }
                    }
                }
            }
        }
        $inputChain[] = sprintf('-A INPUT -j REJECT --reject-with %s', 4 === $inetFamily ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');

        return $inputChain;
    }
}
