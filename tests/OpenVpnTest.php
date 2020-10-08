<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Common\HttpClient\ServerClient;
use LC\Node\OpenVpn;
use PHPUnit\Framework\TestCase;

class OpenVpnTest extends TestCase
{
    /** @var OpenVpn */
    private $openVpn;

    private $serverClient;

    private $tmpDir;

    public function setUp()
    {
        // create temporary directory
        $tmpDir = sprintf('%s/%s', sys_get_temp_dir(), bin2hex(random_bytes(16)));
        mkdir($tmpDir, 0700, true);
        $this->tmpDir = $tmpDir;
        $this->openVpn = new OpenVpn($tmpDir, false);
        $this->serverClient = new ServerClient(
            new TestHttpClient(),
            'openVpnServerClient'
        );
    }

    public function testWriteProfiles()
    {
        $this->openVpn->writeProfiles($this->serverClient, 'openvpn', 'openvpn', []);
        $this->assertSame(
            trim(file_get_contents(sprintf('%s/internet-0.conf', $this->tmpDir))),
            trim(file_get_contents(sprintf('%s/data/internet-0.conf', __DIR__)))
        );
    }

    public function testcheckOverlap()
    {
        // IPv4
        $this->assertEmpty(OpenVpn::checkOverlap(['192.168.0.0/24', '10.0.0.0/8']));
        $this->assertEmpty(OpenVpn::checkOverlap(['192.168.0.0/24', '192.168.1.0/24']));
        $this->assertEmpty(OpenVpn::checkOverlap(['192.168.0.0/25', '192.168.0.128/25']));

        $this->assertSame(
            [
                [
                    '192.168.0.0/24',
                    '192.168.0.0/24',
                ],
            ],
            OpenVpn::checkOverlap(['192.168.0.0/24', '192.168.0.0/24'])
        );

        $this->assertSame(
            [
                [
                    '192.168.0.0/25',
                    '192.168.0.0/24',
                ],
            ],
            OpenVpn::checkOverlap(['192.168.0.0/24', '192.168.0.0/25'])
        );

        // IPv6
        $this->assertEmpty(OpenVpn::checkOverlap(['fd00::/8', 'fc00::/8']));
        $this->assertEmpty(OpenVpn::checkOverlap(['fd11:1111:1111:1111::/64', 'fd11:1111:1111:1112::/64']));

        $this->assertSame(
            [
                [
                    'fd11:1111:1111:1111::/64',
                    'fd11:1111:1111:1111::/64',
                ],
            ],
            OpenVpn::checkOverlap(['fd11:1111:1111:1111::/64', 'fd11:1111:1111:1111::/64'])
        );

        $this->assertSame(
            [
                [
                    'fd11:1111:1111:1111::/100',
                    'fd11:1111:1111:1111::/64',
                ],
            ],
            OpenVpn::checkOverlap(['fd11:1111:1111:1111::/64', 'fd11:1111:1111:1111::/100'])
        );
    }
}
