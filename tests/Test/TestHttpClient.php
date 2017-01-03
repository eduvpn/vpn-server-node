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
    public function get($requestUri)
    {
        switch ($requestUri) {
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData = [])
    {
        switch ($requestUri) {
            case 'connectionServerClient/connect':
                if ('foo_bar' === $postData['common_name']) {
                    return self::wrap('connect');
                }

                return self::wrapError('connect', 'error message');
            case 'connectionServerClient/disconnect':
                return self::wrap('disconnect');

            case 'otpServerClient/verify_two_factor':
                if ('123456' === $postData['two_factor_value']) {
                    return self::wrap('verify_two_factor');
                }

                return self::wrapError('verify_two_factor', 'invalid TOTP key');
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    private static function wrap($key, $statusCode = 200)
    {
        return [
            $statusCode,
            [
                $key => [
                    'ok' => true,
                ],
            ],
        ];
    }

    private static function wrapError($key, $errorMessage, $statusCode = 200)
    {
        return [
            $statusCode,
            [
                $key => [
                    'ok' => false,
                    'error' => $errorMessage,
                ],
            ],
        ];
    }
}
