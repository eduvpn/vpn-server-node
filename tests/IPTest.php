<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Node\IP;
use PHPUnit\Framework\TestCase;

class IPTest extends TestCase
{
    public function testIPv4One()
    {
        $ip = new IP('192.168.1.0/24');
        $splitRange = $ip->split(1);
        $this->assertSame(1, \count($splitRange));
        $this->assertSame('192.168.1.0/24', (string) $splitRange[0]);
    }

    public function testIPv4Two()
    {
        $ip = new IP('192.168.1.0/24');
        $splitRange = $ip->split(2);
        $this->assertSame(2, \count($splitRange));
        $this->assertSame('192.168.1.0/25', (string) $splitRange[0]);
        $this->assertSame('192.168.1.128/25', (string) $splitRange[1]);
    }

    public function testIPv4Four()
    {
        $ip = new IP('192.168.1.0/24');
        $splitRange = $ip->split(4);
        $this->assertSame(4, \count($splitRange));
        $this->assertSame('192.168.1.0/26', (string) $splitRange[0]);
        $this->assertSame('192.168.1.64/26', (string) $splitRange[1]);
        $this->assertSame('192.168.1.128/26', (string) $splitRange[2]);
        $this->assertSame('192.168.1.192/26', (string) $splitRange[3]);
    }

    public function testIPv6One()
    {
        $ip = new IP('1111:2222:3333:4444::/64');
        $splitRange = $ip->split(1);
        $this->assertSame(1, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
    }

    public function testIPv6OneWithMinSpace()
    {
        $ip = new IP('1111:2222:3333:4444::/112');
        $splitRange = $ip->split(1);
        $this->assertSame(1, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
    }

    public function testIPv6Two()
    {
        $ip = new IP('1111:2222:3333:4444::/64');
        $splitRange = $ip->split(2);
        $this->assertSame(2, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
        $this->assertSame('1111:2222:3333:4444::1:0/112', (string) $splitRange[1]);
    }

    public function testIPv6Four()
    {
        $ip = new IP('1111:2222:3333:4444::/64');
        $splitRange = $ip->split(4);
        $this->assertSame(4, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
        $this->assertSame('1111:2222:3333:4444::1:0/112', (string) $splitRange[1]);
        $this->assertSame('1111:2222:3333:4444::2:0/112', (string) $splitRange[2]);
        $this->assertSame('1111:2222:3333:4444::3:0/112', (string) $splitRange[3]);
    }

    public function testGetFirstHost()
    {
        $ip = new IP('192.168.1.0/24');
        $splitRange = $ip->split(4);
        $this->assertSame(4, \count($splitRange));
        $this->assertSame('192.168.1.0/26', (string) $splitRange[0]);
        $this->assertSame('192.168.1.1', $splitRange[0]->getFirstHost());
        $this->assertSame('192.168.1.64/26', (string) $splitRange[1]);
        $this->assertSame('192.168.1.65', $splitRange[1]->getFirstHost());
        $this->assertSame('192.168.1.128/26', (string) $splitRange[2]);
        $this->assertSame('192.168.1.129', $splitRange[2]->getFirstHost());
        $this->assertSame('192.168.1.192/26', (string) $splitRange[3]);
        $this->assertSame('192.168.1.193', $splitRange[3]->getFirstHost());
    }

    public function testGetFirstHost6()
    {
        $ip = new IP('1111:2222:3333:4444::/64');
        $splitRange = $ip->split(4);
        $this->assertSame(4, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
        $this->assertSame('1111:2222:3333:4444::1', $splitRange[0]->getFirstHost());
        $this->assertSame('1111:2222:3333:4444::1:0/112', (string) $splitRange[1]);
        $this->assertSame('1111:2222:3333:4444::1:1', $splitRange[1]->getFirstHost());
        $this->assertSame('1111:2222:3333:4444::2:0/112', (string) $splitRange[2]);
        $this->assertSame('1111:2222:3333:4444::2:1', $splitRange[2]->getFirstHost());
        $this->assertSame('1111:2222:3333:4444::3:0/112', (string) $splitRange[3]);
        $this->assertSame('1111:2222:3333:4444::3:1', $splitRange[3]->getFirstHost());
    }

    public function testIPv4NonFirstTwo()
    {
        $ip = new IP('192.168.1.128/24');
        $splitRange = $ip->split(2);
        $this->assertSame(2, \count($splitRange));
        $this->assertSame('192.168.1.0/25', (string) $splitRange[0]);
        $this->assertSame('192.168.1.128/25', (string) $splitRange[1]);
    }

    public function testIPv6NonFirstTwo()
    {
        $ip = new IP('1111:2222:3333:4444::ffff/64');
        $splitRange = $ip->split(2);
        $this->assertSame(2, \count($splitRange));
        $this->assertSame('1111:2222:3333:4444::/112', (string) $splitRange[0]);
        $this->assertSame('1111:2222:3333:4444::1:0/112', (string) $splitRange[1]);
    }
}
