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

namespace SURFnet\VPN\Node;

use PHPUnit_Framework_TestCase;

class OpenVpnTest extends PHPUnit_Framework_TestCase
{
    public function testVpnProto()
    {
        $this->assertSame(['udp', 'tcp-server'], OpenVpn::getVpnProto('0.0.0.0'));
        $this->assertSame(['udp6', 'tcp6-server'], OpenVpn::getVpnProto('::'));
    }

    public function testGetProtoPortAddressOne()
    {
        $this->assertSame(
            [
                ['udp', 1194],
            ],
            OpenVpn::getProtoPortListen(1, '0.0.0.0', false)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
            ],
            OpenVpn::getProtoPortListen(1, '::', false)
        );

        $this->assertSame(
            [
                ['udp', 1194],
            ],
            OpenVpn::getProtoPortListen(1, '0.0.0.0', true)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
            ],
            OpenVpn::getProtoPortListen(1, '::', true)
        );
    }

    public function testGetProtoPortAddressTwo()
    {
        $this->assertSame(
            [
                ['udp', 1194],
                ['tcp-server', 443],
            ],
            OpenVpn::getProtoPortListen(2, '0.0.0.0', false)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['tcp6-server', 443],
            ],
            OpenVpn::getProtoPortListen(2, '::', false)
        );

        $this->assertSame(
            [
                ['udp', 1194],
                ['tcp-server', 1194],
            ],
            OpenVpn::getProtoPortListen(2, '0.0.0.0', true)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['tcp6-server', 1194],
            ],
            OpenVpn::getProtoPortListen(2, '::', true)
        );
    }

    public function testGetProtoPortAddressFour()
    {
        $this->assertSame(
            [
                ['udp', 1194],
                ['udp', 1195],
                ['tcp-server', 1194],
                ['tcp-server', 443],
            ],
            OpenVpn::getProtoPortListen(4, '0.0.0.0', false)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['udp6', 1195],
                ['tcp6-server', 1194],
                ['tcp6-server', 443],
            ],
            OpenVpn::getProtoPortListen(4, '::', false)
        );

        $this->assertSame(
            [
                ['udp', 1194],
                ['udp', 1195],
                ['tcp-server', 1194],
                ['tcp-server', 1195],
            ],
            OpenVpn::getProtoPortListen(4, '0.0.0.0', true)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['udp6', 1195],
                ['tcp6-server', 1194],
                ['tcp6-server', 1195],
            ],
            OpenVpn::getProtoPortListen(4, '::', true)
        );
    }

    public function testGetProtoPortAddressEight()
    {
        $this->assertSame(
            [
                ['udp', 1194],
                ['udp', 1195],
                ['udp', 1196],
                ['udp', 1197],
                ['udp', 1198],
                ['tcp-server', 1194],
                ['tcp-server', 1195],
                ['tcp-server', 443],
            ],
            OpenVpn::getProtoPortListen(8, '0.0.0.0', false)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['udp6', 1195],
                ['udp6', 1196],
                ['udp6', 1197],
                ['udp6', 1198],
                ['tcp6-server', 1194],
                ['tcp6-server', 1195],
                ['tcp6-server', 443],
            ],
            OpenVpn::getProtoPortListen(8, '::', false)
        );

        $this->assertSame(
            [
                ['udp', 1194],
                ['udp', 1195],
                ['udp', 1196],
                ['udp', 1197],
                ['udp', 1198],
                ['tcp-server', 1194],
                ['tcp-server', 1195],
                ['tcp-server', 1196],
            ],
            OpenVpn::getProtoPortListen(8, '0.0.0.0', true)
        );

        $this->assertSame(
            [
                ['udp6', 1194],
                ['udp6', 1195],
                ['udp6', 1196],
                ['udp6', 1197],
                ['udp6', 1198],
                ['tcp6-server', 1194],
                ['tcp6-server', 1195],
                ['tcp6-server', 1196],

            ],
            OpenVpn::getProtoPortListen(8, '::', true)
        );
    }
}
