<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Common\Config;

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
     * @param array<string,\LC\Common\Config> $profileConfigList
     *
     * @return string
     */
    public function get(Config $firewallConfig, array $profileConfigList)
    {
        $ipFamily = $this->ipFamily;
        $natRulesList = [];

        // INPUT
        $inputRulesList = self::expandRules($firewallConfig->getItem('inputRules'));

        // NAT
        if ($firewallConfig->hasSection('natRules')) {
            foreach ($profileConfigList as $profileId => $profileConfig) {
                $natRulesConfig = $firewallConfig->getSection('natRules');
                if (!$natRulesConfig->hasSection($profileId)) {
                    // no entry in natRules for this profile ID
                    continue;
                }

                $natRuleConfig = $natRulesConfig->getSection($profileId);
                $enableNat = $natRuleConfig->optionalItem('enableNat', []);

                // IPv4
                $natRulesList[] = [
                    'ipRange' => new IP($profileConfig->getItem('range')),
                    'enableNat' => \in_array('IPv4', $enableNat, true),
                ];

                // IPv6
                $natRulesList[] = [
                    'ipRange' => new IP($profileConfig->getItem('range6')),
                    'enableNat' => \in_array('IPv6', $enableNat, true),
                ];
            }
        }

        ob_start();
        include __DIR__.'/tpl/iptables.php';

        return ob_get_clean();
    }

    /**
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
