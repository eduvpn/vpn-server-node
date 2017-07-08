<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
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
