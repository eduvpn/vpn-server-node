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

    public function connect(string $profileId, string $commonName, string $ipFour, string $ipSix, string $connectedAt): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/connect',
            [
                'profile_id' => $profileId,
                'common_name' => $commonName,
                'ip_four' => $ipFour,
                'ip_six' => $ipSix,
                'connected_at' => $connectedAt,
            ]
        );
        if ('OK' !== $httpResponse->getBody()) {
            // XXX fix exception
            throw new ConnectionException();
        }
    }

    public function disconnect(string $profileId, string $commonName, string $ipFour, string $ipSix, string $connectedAt, string $connectionDuration, string $bytesReceived, string $bytesSent): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/disconnect',
            [
                'profile_id' => $profileId,
                'common_name' => $commonName,
                'ip_four' => $ipFour,
                'ip_six' => $ipSix,
                'connected_at' => $connectedAt,
                'disconnected_at' => (string) ((int) $connectedAt + (int) $connectionDuration),
                'bytes_transferred' => (string) ((int) $bytesReceived + (int) $bytesSent),
            ]
        );
        if ('OK' !== $httpResponse->getBody()) {
            // XXX fix exception
            throw new ConnectionException();
        }
    }
}
