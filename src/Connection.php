<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Common\HttpClient\ServerClient;
use RuntimeException;

class Connection
{
    /** @var \LC\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(ServerClient $serverClient)
    {
        $this->serverClient = $serverClient;
    }

    /**
     * @return void
     */
    public function connect(array $envData)
    {
        $this->serverClient->post(
            'connect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'originating_ip' => self::requireOriginatingIp($envData['trusted_ip'], $envData['trusted_ip6']),
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
            ]
        );
    }

    /**
     * @return void
     */
    public function disconnect(array $envData)
    {
        $this->serverClient->post(
            'disconnect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'originating_ip' => self::requireOriginatingIp($envData['trusted_ip'], $envData['trusted_ip6']),
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
                'disconnected_at' => $envData['time_unix'] + $envData['time_duration'],
                'bytes_transferred' => $envData['bytes_received'] + $envData['bytes_sent'],
            ]
        );
    }

    /**
     * Make sure that we have either an IPv4 or an IPv6 originating IP address.
     *
     * @param string|false $ipFour
     * @param string|false $ipSix
     *
     * @return string
     */
    private static function requireOriginatingIp($ipFour, $ipSix)
    {
        if (false !== $ipFour) {
            return $ipFour;
        }

        if (false !== $ipSix) {
            return $ipSix;
        }

        throw new RuntimeException('neither IPv4 nor IPv6 originating IP is available');
    }
}
