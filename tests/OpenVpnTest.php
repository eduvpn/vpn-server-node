<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Node\ConfigWriter;
use PHPUnit\Framework\TestCase;

class OpenVpnTest extends TestCase
{
    public function testWrite(): void
    {
        $tmpDir = sprintf('%s/%s', sys_get_temp_dir(), bin2hex(random_bytes(16)));
        mkdir($tmpDir, 0700, true);
        $configWriter = new ConfigWriter($tmpDir, new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php');
        $configWriter->write([]);
        $this->assertSame('default-0', file_get_contents($tmpDir.'/default-0.conf'));
        $this->assertSame('default-1', file_get_contents($tmpDir.'/default-1.conf'));
    }
}
