<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Node\HttpClient\HttpClientInterface;
use LC\Node\HttpClient\HttpClientRequest;
use LC\Node\HttpClient\HttpClientResponse;

class TestHttpClient implements HttpClientInterface
{
    public function send(HttpClientRequest $httpClientRequest): HttpClientResponse
    {
        switch ($httpClientRequest->requestUrl()) {
            case 'http://localhost/vpn-user-portal/node-api.php/connect':
                if ('profile_id=profile_id&common_name=common_name&ip_four=ip_four&ip_six=ip_six&originating_ip=orig_ip_four&connected_at=connected_at' === $httpClientRequest->postParameters()) {
                    return new HttpClientResponse(200, '', 'OK');
                }

                return new HttpClientResponse(200, '', 'ERR');

            case 'http://localhost/vpn-user-portal/node-api.php/disconnect':
                if ('profile_id=profile_id&common_name=common_name&ip_four=ip_four&ip_six=ip_six&originating_ip=orig_ip_four&connected_at=connected_at&disconnected_at=0&bytes_transferred=0' === $httpClientRequest->postParameters()) {
                    return new HttpClientResponse(200, '', 'OK');
                }

                return new HttpClientResponse(200, '', 'ERR');

            case 'http://localhost/vpn-user-portal/node-api.php/server_config':
                return new HttpClientResponse(200, '', 'default-0.conf:ZGVmYXVsdC0w'."\r\ndefault-1.conf:ZGVmYXVsdC0x");

            default:
                return new HttpClientResponse(404, '', 'Not Found');
        }
    }
}
