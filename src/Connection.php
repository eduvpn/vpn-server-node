<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Common\HttpClient\ServerClient;

class Connection
{
    /** @var \LC\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(ServerClient $serverClient)
    {
        $this->serverClient = $serverClient;
    }

    public function connect(array $envData): void
    {
        $this->serverClient->post(
            'connect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
            ]
        );
    }

    public function disconnect(array $envData): void
    {
        $this->serverClient->post(
            'disconnect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
                'disconnected_at' => $envData['time_unix'] + $envData['time_duration'],
                'bytes_transferred' => $envData['bytes_received'] + $envData['bytes_sent'],
            ]
        );
    }
}
