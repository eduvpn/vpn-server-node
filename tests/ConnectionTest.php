<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LetsConnect\Node\Tests;

use LetsConnect\Common\HttpClient\ServerClient;
use LetsConnect\Node\Connection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
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
     * @expectedException \LetsConnect\Common\HttpClient\Exception\ApiException
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
