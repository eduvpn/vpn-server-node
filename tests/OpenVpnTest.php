<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
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

    protected function setUp(): void
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

    public function testWriteProfiles(): void
    {
        $this->openVpn->writeProfiles($this->serverClient, 'openvpn', 'openvpn', []);
        $this->assertSame(
            trim(file_get_contents(sprintf('%s/internet-0.conf', $this->tmpDir))),
            trim(file_get_contents(sprintf('%s/data/internet-0.conf', __DIR__)))
        );
    }
}
