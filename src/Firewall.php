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
     * @param array<string,\LetsConnect\Common\Config> $profileConfig
     *
     * @return string
     */
    public function get(Config $firewallConfig, array $profileConfigList)
    {
        $ipFamily = $this->ipFamily;
        $inputFilterList = self::expandRules($firewallConfig->getItem('inputRules'));

        $natSrcNetList = [];
        if ($firewallConfig->hasSection('natConfig')) {
            $natConfig = $firewallConfig->getSection('natConfig');
            foreach ($profileConfigList as $profileId => $profileConfig) {
                if (!$natConfig->hasSection($profileId)) {
                    continue;
                }
                $natProfileConfig = $natConfig->getSection($profileId);
                $natFamily = $natProfileConfig->optionalItem('natFamily', ['IPv4', 'IPv6']);
                if (\in_array('IPv4', $natFamily, true)) {
                    $natSrcNetList[] = new IP($profileConfig->getItem('range'));
                }
                if (\in_array('IPv6', $natFamily, true)) {
                    $natSrcNetList[] = new IP($profileConfig->getItem('range6'));
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
