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

namespace SURFnet\VPN\Node\Tests;

use PHPUnit_Framework_TestCase;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Connection;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    /** @var Connection */
    private $connection;

    public function setUp()
    {
        $this->connection = new Connection(
            new ServerClient(
                new TestHttpClient(),
                'connectionServerClient'
            )
        );
    }

    public function testValidConnection()
    {
        $this->connection->connect(
            [
                'common_name' => 'foo_bar',
                'PROFILE_ID' => 'internet',
                'time_unix' => '12345678',
                'ifconfig_pool_remote_ip' => '10.0.42.0',
                'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
            ]
        );
    }

    /**
     * @expectedException \SURFnet\VPN\Common\HttpClient\Exception\ApiException
     * @expectedExceptionMessage error message
     */
    public function testInvalidConnection()
    {
        $this->connection->connect(
            [
                'common_name' => 'foo_baz',
                'PROFILE_ID' => 'internet',
                'time_unix' => '12345678',
                'ifconfig_pool_remote_ip' => '10.0.42.0',
                'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
            ]
        );
    }

    public function testDisconnect()
    {
        $this->connection->disconnect(
            [
                'common_name' => 'foo_bar',
                'PROFILE_ID' => 'acl2',
                'time_unix' => '12345678',
                'ifconfig_pool_remote_ip' => '10.0.42.0',
                'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                'time_duration' => '3600',
                'bytes_sent' => '123456',
                'bytes_received' => '444444',
            ]
        );
    }
}
