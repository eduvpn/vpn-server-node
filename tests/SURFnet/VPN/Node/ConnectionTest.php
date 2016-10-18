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

require_once sprintf('%s/Test/TestHttpClient.php', __DIR__);

use PHPUnit_Framework_TestCase;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use Psr\Log\NullLogger;
use SURFnet\VPN\Node\Test\TestHttpClient;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    /** @var Connection */
    private $connection;

    public function setUp()
    {
        $this->connection = new Connection(
            new NullLogger(),
            new ServerClient(
                new TestHttpClient(),
                'connectionServerClient'
            )
        );
    }

    public function testValidConnection()
    {
        $this->assertTrue(
            $this->connection->connect(
                [
                    'common_name' => 'foo_bar',
                    'POOL_ID' => 'internet',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                ]
            )
        );
    }

    public function testDisabledUser()
    {
        $this->assertFalse(
            $this->connection->connect(
                [
                    'common_name' => 'bar_bar',
                    'POOL_ID' => 'internet',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                ]
            )
        );
    }

    public function testDisabledCommonName()
    {
        $this->assertFalse(
            $this->connection->connect(
                [
                    'common_name' => 'foo_baz',
                    'POOL_ID' => 'internet',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                ]
            )
        );
    }

    public function testAclValid()
    {
        $this->assertTrue(
            $this->connection->connect(
                [
                    'common_name' => 'foo_bar',
                    'POOL_ID' => 'acl',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                ]
            )
        );
    }

    public function testAclInvalid()
    {
        $this->assertFalse(
            $this->connection->connect(
                [
                    'common_name' => 'foo_bar',
                    'POOL_ID' => 'acl2',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                ]
            )
        );
    }

    public function testDisconnect()
    {
        $this->assertTrue(
            $this->connection->disconnect(
                [
                    'common_name' => 'foo_bar',
                    'POOL_ID' => 'acl2',
                    'time_unix' => '12345678',
                    'ifconfig_pool_remote_ip' => '10.0.42.0',
                    'ifconfig_pool_remote_ip6' => 'fd00:4242:4242:4242::',
                    'time_duration' => '3600',
                    'bytes_sent' => '123456',
                    'bytes_received' => '444444',
                ]
            )
        );
    }
}
