<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\Tests;

use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\TwoFactor;

class TwoFactorTest extends PHPUnit_Framework_TestCase
{
    /** @var TwoFactor */
    private $twoFactor;

    public function setUp()
    {
        $this->twoFactor = new TwoFactor(
            new NullLogger(),
            new ServerClient(
                new TestHttpClient(),
                'otpServerClient'
            )
        );
    }

    public function testValidTwoFactor()
    {
        $this->twoFactor->verify(
            [
                'username' => 'totp',
                'common_name' => '12345678901234567890123456789012',
                'password' => '123456',
            ]
        );
    }

    /**
     * @expectedException \SURFnet\VPN\Common\HttpClient\Exception\ApiException
     * @expectedExceptionMessage invalid TOTP key
     */
    public function testNoInvalidTwoFactorKey()
    {
        $this->twoFactor->verify(
            [
                'username' => 'totp',
                'common_name' => '12345678901234567890123456789012',
                'password' => '654321',
            ]
        );
    }
}
