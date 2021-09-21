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
use LC\Node\HttpClient\HttpClientResponse;

class TestHttpClient implements HttpClientInterface
{
    /**
     * @param array<string,null|string> $postData
     */
    public function post(string $requestUrl, array $postData): HttpClientResponse
    {
        switch ($requestUrl) {
            case 'http://localhost/vpn-user-portal/node-api.php/connect':
                if ('common_name' === $postData['common_name']) {
                    return new HttpClientResponse(200, 'OK');
                }

                return new HttpClientResponse(200, 'ERR');

            case 'http://localhost/vpn-user-portal/node-api.php/disconnect':
                if ('common_name' === $postData['common_name']) {
                    return new HttpClientResponse(200, 'OK');
                }

                return new HttpClientResponse(200, 'ERR');

            case 'http://localhost/vpn-user-portal/node-api.php/server_config':
                return new HttpClientResponse(200, 'default-0.conf:ZGVmYXVsdC0w'."\r\ndefault-1.conf:ZGVmYXVsdC0x");

            default:
                return new HttpClientResponse(404, 'Not Found');
        }
    }
}
