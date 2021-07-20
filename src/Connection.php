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

    public function connect(string $profileId, string $certOrgUnit, string $commonName, ?string $origIpFour, ?string $origIpSix, string $ipFour, string $ipSix, string $connectedAt): void
    {
        if ($profileId !== $certOrgUnit) {
            throw new ConnectionException('client certificate has OU "'.$certOrgUnit.'", but requires "'.$profileId.'" for this profile');
        }

        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/connect',
            [
                'profile_id' => $profileId,
                'common_name' => $commonName,
                'ip_four' => $ipFour,
                'ip_six' => $ipSix,
                'originating_ip_four' => $origIpFour,
                'originating_ip_six' => $origIpSix,
                'connected_at' => $connectedAt,
            ]
        );

        if (200 !== $httpResponse->getCode() || 'OK' !== $httpResponse->getBody()) {
            throw new ConnectionException('unable to connect');
        }
    }

    public function disconnect(string $profileId, string $commonName, ?string $origIpFour, ?string $origIpSix, string $ipFour, string $ipSix, string $connectedAt, string $connectionDuration, string $bytesReceived, string $bytesSent): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/disconnect',
            [
                'profile_id' => $profileId,
                'common_name' => $commonName,
                'ip_four' => $ipFour,
                'ip_six' => $ipSix,
                'originating_ip_four' => $origIpFour,
                'originating_ip_six' => $origIpSix,
                'connected_at' => $connectedAt,
                'disconnected_at' => (string) ((int) $connectedAt + (int) $connectionDuration),
                'bytes_transferred' => (string) ((int) $bytesReceived + (int) $bytesSent),
            ]
        );

        if (200 !== $httpResponse->getCode() || 'OK' !== $httpResponse->getBody()) {
            throw new ConnectionException('unable to disconnect');
        }
    }
}
