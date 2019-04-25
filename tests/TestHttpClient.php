<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Common\HttpClient\HttpClientInterface;
use RuntimeException;

class TestHttpClient implements HttpClientInterface
{
    public function get($requestUri)
    {
        switch ($requestUri) {
            case 'openVpnServerClient/instance_number':
                return self::wrapData('instance_number', 1);

            case 'openVpnServerClient/profile_list':
                return self::wrapData('profile_list', '{"internet":{"defaultGateway":true,"routes":[],"dns":["8.8.8.8","8.8.4.4","2001:4860:4860::8888","2001:4860:4860::8844"],"useNat":true,"twoFactor":false,"clientToClient":false,"listen":"::","enableLog":false,"enableAcl":false,"aclGroupList":[],"managementIp":"4.3.2.1","blockSmb":false,"reject4":false,"reject6":true,"vpnProtoPorts":["udp\/1194","tcp\/1194"],"hideProfile":false,"tlsCrypt":false,"authPlugin":false,"legacySupport":true,"profileNumber":1,"displayName":"Internet Access","extIf":"eth0","range":"10.25.210.0\/24","range6":"fd00:4242:4242:4242::\/64","hostName":"foo"},"nw-testbed":{"defaultGateway":true,"routes":[],"dns":["8.8.8.8","8.8.4.4","2001:4860:4860::8888","2001:4860:4860::8844"],"useNat":true,"twoFactor":true,"clientToClient":false,"listen":"::","enableLog":false,"enableAcl":false,"aclGroupList":[],"managementIp":"127.0.0.1","blockSmb":false,"reject4":false,"reject6":false,"vpnProtoPorts":["udp\/1194","tcp\/1194"],"hideProfile":false,"tlsCrypt":false,"authPlugin":false,"legacySupport":true,"profileNumber":2,"displayName":"NW Testbed","extIf":"eth0","range":"10.42.68.0\/24","range6":"fd91:e178:38aa:56e0::\/60","hostName":"internet.foo.bar"}}');
            default:
                throw new RuntimeException(sprintf('unexpected requestUri "%s"', $requestUri));
        }
    }

    public function post($requestUri, array $postData = [])
    {
        switch ($requestUri) {
            case 'openVpnServerClient/add_server_certificate':
                return self::wrapData('add_server_certificate', '{"certificate":"-----BEGIN CERTIFICATE----X-----END CERTIFICATE-----","private_key":"-----BEGIN PRIVATE KEY-----Y-----END PRIVATE KEY-----","valid_from":1509909938,"valid_to":1541445938,"ta":"#\n# 2048 bit OpenVPN static key\n#\n-----BEGIN OpenVPN Static key V1-----A-----END OpenVPN Static key V1-----\n","ca":"-----BEGIN CERTIFICATE-----B-----END CERTIFICATE-----"}');

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
            json_encode(
                [
                    $key => [
                        'ok' => true,
                    ],
                ]
            ),
        ];
    }

    private static function wrapError($key, $errorMessage, $statusCode = 200)
    {
        return [
            $statusCode,
            json_encode(
                [
                    $key => [
                        'ok' => false,
                        'error' => $errorMessage,
                    ],
                ]
            ),
        ];
    }

    private static function wrapData($key, $jsonData, $statusCode = 200)
    {
        return [
            $statusCode,
            json_encode(
                [
                    $key => [
                        'ok' => true,
                        'data' => json_decode($jsonData, true),
                    ],
                ]
            ),
        ];
    }
}
