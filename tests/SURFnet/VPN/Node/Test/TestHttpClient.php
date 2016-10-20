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

use SURFnet\VPN\Common\HttpClient\HttpClientInterface;
use RuntimeException;
use SURFnet\VPN\Common\ProfileConfig;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri)
    {
        switch ($requestUri) {
            case 'serverClient/has_otp_secret?user_id=foo':
                return self::wrap('has_otp_secret', true);
            case 'serverClient/has_otp_secret?user_id=bar':
                return self::wrap('has_otp_secret', false);

            case 'connectionServerClient/is_disabled_user?user_id=foo':
                return self::wrap('is_disabled_user', false);
            case 'connectionServerClient/is_disabled_user?user_id=bar':
                return self::wrap('is_disabled_user', true);
            case 'connectionServerClient/is_disabled_common_name?common_name=foo_bar':
                return self::wrap('is_disabled_common_name', false);
            case 'connectionServerClient/is_disabled_common_name?common_name=foo_baz':
                return self::wrap('is_disabled_common_name', true);
            case 'connectionServerClient/server_profile?profile_id=internet':
                $profileConfig = new ProfileConfig([]);

                return self::wrap('server_profile', $profileConfig->v());
            case 'connectionServerClient/server_profile?profile_id=acl':
                $profileConfig = new ProfileConfig(
                    [
                        'enableAcl' => true,
                        'aclGroupList' => ['all'],
                    ]
                );

                return self::wrap('server_profile', $profileConfig->v());
            case 'connectionServerClient/server_profile?profile_id=acl2':
                $profileConfig = new ProfileConfig(
                    [
                        'enableAcl' => true,
                        'aclGroupList' => ['students'],
                    ]
                );

                return self::wrap('server_profile', $profileConfig->v());
            case 'connectionServerClient/user_groups?user_id=foo':
                return self::wrap(
                    'user_groups',
                    [
                        ['id' => 'all', 'displayName' => 'All'],
                    ]
                );
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData)
    {
        switch ($requestUri) {
            case 'serverClient/verify_otp_key':
                if ('123456' === $postData['otp_key']) {
                    return self::wrap('verify_otp_key', true);
                }

                return self::wrap('verify_otp_key', false);
            case 'connectionServerClient/log_connect':
                return self::wrap('log_connect', true);
            case 'connectionServerClient/log_disconnect':
                return self::wrap('log_disconnect', true);
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    private static function wrap($key, $response)
    {
        return [
            'data' => [
                $key => $response,
            ],
        ];
    }
}
