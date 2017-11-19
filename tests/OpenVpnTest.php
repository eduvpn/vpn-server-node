<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\Tests;

use PHPUnit\Framework\TestCase;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\OpenVpn;

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
        $this->openVpn = new OpenVpn($tmpDir);
        $this->serverClient = new ServerClient(
            new TestHttpClient(),
            'openVpnServerClient'
        );
    }

    public function testWriteProfiles()
    {
        $this->openVpn->writeProfiles($this->tmpDir, $this->serverClient, 'default', 'openvpn', 'openvpn', true);
        $this->assertSame(
            file_get_contents(sprintf('%s/default-internet-0.conf', $this->tmpDir)),
            file_get_contents(sprintf('%s/data/default-internet-0.conf', __DIR__))
        );
    }
}
