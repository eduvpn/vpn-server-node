<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node;

use SURFnet\VPN\Common\Config;

class FirewallConfig extends Config
{
    public function __construct(array $configData)
    {
        parent::__construct($configData);
    }

    public static function defaultConfig()
    {
        return [
            'instanceList' => [
                'default',
            ],
            'inputChain' => [
                'tcp' => ['22', '80', '443', '1194:1195'],
                'udp' => ['1194:1201'],
                'trustedInterfaces' => [],
            ],
        ];
    }
}
