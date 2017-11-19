<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\Tests;

use SURFnet\VPN\Node\OpenVpn\Exception\ManagementSocketException;
use SURFnet\VPN\Node\OpenVpn\ManagementSocketInterface;

class TestSocket implements ManagementSocketInterface
{
    /** @var bool */
    private $connectFail;

    /** @var string|null */
    private $socketAddress;

    /** @var string */
    private $testSocketDir;

    public function __construct($connectFail = false)
    {
        $this->connectFail = $connectFail;
        $this->socketAddress = null;
        $this->testSocketDir = sprintf('%s/TestSocket', __DIR__);
    }

    public function open($socketAddress, $timeOut = 5)
    {
        $this->socketAddress = $socketAddress;
        if ($this->connectFail) {
            throw new ManagementSocketException('unable to connect to socket');
        }
    }

    public function command($command)
    {
        if ('status 2' === $command) {
            if ('tcp://127.0.0.1:11940' === $this->socketAddress) {
                // send back the returnData as an array
                return explode("\n", file_get_contents(sprintf('%s/status_with_clients.txt', $this->testSocketDir)));
            } else {
                return explode("\n", file_get_contents(sprintf('%s/status_no_clients.txt', $this->testSocketDir)));
            }
        } elseif ('kill' === $command) {
            if ('tcp://127.0.0.1:11940' === $this->socketAddress) {
                return explode("\n", file_get_contents(sprintf('%s/kill_success.txt', $this->testSocketDir)));
            } else {
                return explode("\n", file_get_contents(sprintf('%s/kill_error.txt', $this->testSocketDir)));
            }
        }
    }

    public function close()
    {
        $this->socketAddress = null;
    }
}
