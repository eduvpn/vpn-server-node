<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LetsConnect\Node;

use LetsConnect\Common\Config;

class Firewall
{
    /** @var int */
    private $ipFamily;

    /**
     * @param int $ipFamily
     */
    public function __construct($ipFamily)
    {
        $this->ipFamily = $ipFamily;
    }

    /**
     * @param \LetsConnect\Common\Config               $firewallConfig
     * @param array<string,\LetsConnect\Common\Config> $profileConfigList
     *
     * @return string
     */
    public function get(Config $firewallConfig, array $profileConfigList)
    {
        $ipFamily = $this->ipFamily;
        $inputFilterList = [];
        $forwardFilterList = [];
        if ($enableInputRules = $firewallConfig->getItem('enableInputRules')) {
            $inputFilterList = self::expandRules($firewallConfig->getItem('inputRules'));
        }

        // XXX add ports from vpnProtoPorts / exposedVpnProtoPorts as well

        if ($enableForwardRules = $firewallConfig->getItem('enableForwardRules')) {
            if ($firewallConfig->hasSection('forwardRules')) {
                // only enable "FORWARD" firewall when "forwardRules" is set,
                // disable any FORWARD firewalling otherwise...
                foreach ($profileConfigList as $profileId => $profileConfig) {
                    $profileRulesList = $firewallConfig->getSection('forwardRules');
                    if (!$profileRulesList->hasSection($profileId)) {
                        continue;
                    }

                    $profileRules = $profileRulesList->getSection($profileId);
                    $outInterface = $profileRules->optionalItem('outInterface');
                    $enableNat = $profileRules->optionalItem('enableNat', []);

                    // IPv4
                    $forwardFilterList[] = [
                        'ipRange' => new IP($profileConfig->getItem('range')),
                        'outInterface' => $outInterface,
                        'enableNat' => \in_array('IPv4', $enableNat, true),
                    ];

                    // IPv6
                    $forwardFilterList[] = [
                        'ipRange' => new IP($profileConfig->getItem('range6')),
                        'outInterface' => $outInterface,
                        'enableNat' => \in_array('IPv6', $enableNat, true),
                    ];
                }
            }
        }

        ob_start();
        include __DIR__.'/tpl/iptables.php';

        return ob_get_clean();
    }

    /**
     * @param array $ruleList
     *
     * @return array<FirewallRule>
     */
    private static function expandRules(array $ruleList)
    {
        $expandedRules = [];
        foreach ($ruleList as $ruleItem) {
            $rowColIndex = [];
            foreach ($ruleItem as $k => $v) {
                $rowColIndex[$k] = 0;
            }
            do {
                $rowData = [];
                foreach ($rowColIndex as $k => $v) {
                    $rowData[$k] = $ruleItem[$k][$v];
                }
                $expandedRules[] = new FirewallRule($rowData);
                $hasNext = false;
                foreach ($rowColIndex as $k => $v) {
                    if ($v < \count($ruleItem[$k]) - 1) {
                        ++$rowColIndex[$k];
                        $hasNext = true;
                        break;
                    }
                    $rowColIndex[$k] = 0;
                }
            } while ($hasNext);
        }

        return $expandedRules;
    }
}
