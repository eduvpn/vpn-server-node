<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SURFnet\VPN\Node\Test;

use RuntimeException;
use SURFnet\VPN\Common\HttpClient\HttpClientInterface;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri, array $getData = [], array $requestHeaders = [])
    {
        switch ($requestUri) {
            case 'serverClient/has_totp_secret?user_id=foo':
                return self::wrap('has_totp_secret', true);
            case 'serverClient/has_totp_secret?user_id=bar':
                return self::wrap('has_totp_secret', false);
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData, array $requestHeaders = [])
    {
        switch ($requestUri) {
            case 'connectionServerClient/connect':
                if ('foo_bar' === $postData['common_name']) {
                    return self::wrap('connect', ['ok' => true]);
                }

                return self::wrap('connect', ['ok' => false, 'error' => 'error from vpn-server-api']);

            case 'connectionServerClient/disconnect':
                return self::wrap('disconnect', ['ok' => true]);

            case 'serverClient/verify_totp_key':
                if ('123456' === $postData['totp_key']) {
                    return self::wrap('verify_totp_key', true);
                }

                return self::wrap('verify_totp_key', false);
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    private static function wrap($key, $response, $statusCode = 200)
    {
        return [
            $statusCode,
            [
                'data' => [
                    $key => $response,
                ],
            ],
        ];
    }
}
