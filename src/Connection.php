<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Node\Exception\ConnectionException;
use LC\Node\HttpClient\HttpClientInterface;

class Connection
{
    private HttpClientInterface $httpClient;

    private string $apiUrl;

    public function __construct(HttpClientInterface $httpClient, string $apiUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
    }

    public function connect(array $envData): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/connect',
            [],
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
            ]
        );
        if ('OK' !== $httpResponse->getBody()) {
            throw new ConnectionException('', $envData);
        }
    }

    public function disconnect(array $envData): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/disconnect',
            [],
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
        if ('OK' !== $httpResponse->getBody()) {
            throw new ConnectionException('', $envData);
        }
    }
}
