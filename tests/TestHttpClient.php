<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node\Tests;

use Vpn\Node\HttpClient\HttpClientInterface;
use Vpn\Node\HttpClient\HttpClientRequest;
use Vpn\Node\HttpClient\HttpClientResponse;

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
                if ('profile_id=profile_id&common_name=common_name&ip_four=ip_four&ip_six=ip_six&originating_ip=orig_ip_four&connected_at=connected_at&bytes_in=0&bytes_out=0&disconnected_at=0' === $httpClientRequest->postParameters()) {
                    return new HttpClientResponse(200, '', 'OK');
                }

                return new HttpClientResponse(200, '', 'ERR');

            case 'http://localhost/vpn-user-portal/node-api.php/server_config':
                return new HttpClientResponse(200, '', 'default-0.conf:ZGVmYXVsdC0w'."\ndefault-1.conf:ZGVmYXVsdC0x"."\nwg.conf:V0c6e3tQUklWQVRFX0tFWX19");

            default:
                return new HttpClientResponse(404, '', 'Not Found');
        }
    }
}
