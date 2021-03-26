<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Tests;

use LC\Node\Connection;
use LC\Node\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    public function testConnect(): void
    {
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php');
        $connection->connect(
            'profile_id',
            'common_name',
            'ip_four',
            'ip_six',
            'connected_at'
        );
    }

    public function testConnectError(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('unable to connect');
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php');
        $connection->connect(
            'profile_id',
            'common_name_error',
            'ip_four',
            'ip_six',
            'connected_at'
        );
    }

    public function testDisconnect(): void
    {
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php');
        $connection->disconnect(
            'profile_id',
            'common_name',
            'ip_four',
            'ip_six',
            'connected_at',
            'connection_duration',
            'bytes_received',
            'bytes_sent'
        );
    }

    public function testDisconnectError(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('unable to disconnect');
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php');
        $connection->disconnect(
            'profile_id',
            'common_name_error',
            'ip_four',
            'ip_six',
            'connected_at',
            'connection_duration',
            'bytes_received',
            'bytes_sent'
        );
    }
}
