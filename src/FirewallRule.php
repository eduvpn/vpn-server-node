<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LetsConnect\Node;

use LetsConnect\Node\Exception\FirewallRuleException;

class FirewallRule
{
    /** @var string */
    private $ipProto;

    /** @var int */
    private $dstPort;

    /** @var IP|null */
    private $srcNet = null;

    /**
     * @param array<string,int|string|null> $ruleData
     */
    public function __construct(array $ruleData)
    {
        if (!\array_key_exists('proto', $ruleData)) {
            throw new FirewallRuleException('missing "proto"');
        }
        if (!\in_array($ruleData['proto'], ['tcp', 'udp'], true)) {
            throw new FirewallRuleException('"proto" must be either "tcp" or "udp"');
        }
        $this->ipProto = $ruleData['proto'];
        if (!\array_key_exists('dst_port', $ruleData)) {
            throw new FirewallRuleException('missing "dst_port"');
        }
        if (!\is_int($ruleData['dst_port'])) {
            throw new FirewallRuleException('"dst_port" must be an integer');
        }
        if (0 >= $ruleData['dst_port'] || 65535 < $ruleData['dst_port']) {
            throw new FirewallRuleException('0 < "dst_port" < 65535');
        }
        $this->dstPort = $ruleData['dst_port'];
        if (\array_key_exists('src_net', $ruleData)) {
            if (!\is_string($ruleData['src_net'])) {
                throw new FirewallRuleException('"src_net" must be a string');
            }
            $this->srcNet = new IP($ruleData['src_net']);
        }
    }

    /**
     * @return string
     */
    public function getProto()
    {
        return $this->ipProto;
    }

    /**
     * @return int
     */
    public function getDstPort()
    {
        return $this->dstPort;
    }

    /**
     * @return IP|null
     */
    public function getSrcNet()
    {
        return $this->srcNet;
    }
}
