<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2022, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node\Tests;

use PHPUnit\Framework\TestCase;
use Vpn\Node\Config;
use Vpn\Node\ConfigWriter;
use Vpn\Node\FileIO;

/**
 * @internal
 * @coversNothing
 */
final class ConfigWriterTest extends TestCase
{
    public function testWrite(): void
    {
        $config = new Config(
            [
                'apiUrl' => 'http://localhost/vpn-user-portal/node-api.php',
                'nodeNumber' => 0,
            ]
        );
        $tmpDir = sprintf('%s/%s', sys_get_temp_dir(), bin2hex(random_bytes(16)));
        FileIO::mkdir($tmpDir);
        FileIO::mkdir($tmpDir.'/config');
        FileIO::mkdir($tmpDir.'/config/keys');
        FileIO::mkdir($tmpDir.'/openvpn-config');
        FileIO::mkdir($tmpDir.'/wg-config');
        FileIO::write($tmpDir.'/config/keys/wireguard.key', 'sBu1nuSr9w1IAIby38GCl7E/3iDcoVEsKch4hsdGSiI=');
        $configWriter = new ConfigWriter($tmpDir, new TestHttpClient(), $config, 'node-key');
        $configWriter->write();
        static::assertSame('default-0', FileIO::read($tmpDir.'/openvpn-config/default-0.conf'));
        static::assertSame('default-1', FileIO::read($tmpDir.'/openvpn-config/default-1.conf'));
        static::assertSame('WG:sBu1nuSr9w1IAIby38GCl7E/3iDcoVEsKch4hsdGSiI=', FileIO::read($tmpDir.'/wg-config/wg0.conf'));
    }
}
