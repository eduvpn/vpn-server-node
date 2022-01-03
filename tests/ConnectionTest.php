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
use Vpn\Node\Connection;
use Vpn\Node\Exception\ConnectionException;

/**
 * @internal
 * @coversNothing
 */
final class ConnectionTest extends TestCase
{
    public function testConnect(): void
    {
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php', 0, 'node-key');
        $connection->connect(
            'profile_id',
            'profile_id',
            'common_name',
            'orig_ip_four',
            'orig_ip_six',
            'ip_four',
            'ip_six',
            'connected_at'
        );
    }

    public function testConnectError(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('unable to connect');
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php', 0, 'node-key');
        $connection->connect(
            'profile_id',
            'profile_id',
            'common_name_error',
            'orig_ip_four',
            'orig_ip_six',
            'ip_four',
            'ip_six',
            'connected_at'
        );
    }

    public function testConnectWrongProfileWithOu(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('client certificate has OU "wrong_cert_ou", but requires "profile_id" for this profile');
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php', 0, 'node-key');
        $connection->connect(
            'profile_id',
            'wrong_cert_ou',
            'common_name_error',
            'orig_ip_four',
            'orig_ip_six',
            'ip_four',
            'ip_six',
            'connected_at'
        );
    }

    public function testDisconnect(): void
    {
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php', 0, 'node-key');
        $connection->disconnect(
            'profile_id',
            'common_name',
            'orig_ip_four',
            'orig_ip_six',
            'ip_four',
            'ip_six',
            'connected_at',
            '0',
            '0',
            'connection_duration'
        );
    }

    public function testDisconnectError(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('unable to disconnect');
        $connection = new Connection(new TestHttpClient(), 'http://localhost/vpn-user-portal/node-api.php', 0, 'node-key');
        $connection->disconnect(
            'profile_id',
            'common_name_error',
            'orig_ip_four',
            'orig_ip_six',
            'ip_four',
            'ip_six',
            'connected_at',
            'bytes_in',
            'bytes_out',
            'connection_duration'
        );
    }
}
