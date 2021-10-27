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
use LC\Node\HttpClient\HttpClientRequest;

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

        $httpResponse = $this->httpClient->send(
            new HttpClientRequest(
                'POST',
                $this->apiUrl.'/connect',
                [],
                [
                    'profile_id' => $profileId,
                    'common_name' => $commonName,
                    'ip_four' => $ipFour,
                    'ip_six' => $ipSix,
                    'originating_ip' => self::requireOriginatingIp($origIpFour, $origIpSix),
                    'connected_at' => $connectedAt,
                ]
            )
        );

        if (!$httpResponse->isOkay() || 'OK' !== $httpResponse->body()) {
            throw new ConnectionException('unable to connect');
        }
    }

    public function disconnect(string $profileId, string $commonName, ?string $origIpFour, ?string $origIpSix, string $ipFour, string $ipSix, string $connectedAt, string $connectionDuration, string $bytesReceived, string $bytesSent): void
    {
        $httpResponse = $this->httpClient->send(
            new HttpClientRequest(
                'POST',
                $this->apiUrl.'/disconnect',
                [],
                [
                    'profile_id' => $profileId,
                    'common_name' => $commonName,
                    'ip_four' => $ipFour,
                    'ip_six' => $ipSix,
                    'originating_ip' => self::requireOriginatingIp($origIpFour, $origIpSix),
                    'connected_at' => $connectedAt,
                    'disconnected_at' => (string) ((int) $connectedAt + (int) $connectionDuration),
                    'bytes_transferred' => (string) ((int) $bytesReceived + (int) $bytesSent),
                ]
            )
        );

        if (!$httpResponse->isOkay() || 'OK' !== $httpResponse->body()) {
            throw new ConnectionException('unable to disconnect');
        }
    }

    /**
     * Make sure that we have either an IPv4 or an IPv6 originating IP address.
     */
    private static function requireOriginatingIp(?string $ipFour, ?string $ipSix): string
    {
        if (null !== $ipFour) {
            return $ipFour;
        }

        if (null !== $ipSix) {
            return $ipSix;
        }

        throw new ConnectionException('neither IPv4 nor IPv6 originating IP is available');
    }
}
