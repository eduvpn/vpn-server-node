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
use Psr\Log\NullLogger;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Test\TestHttpClient;

class OtpTest extends PHPUnit_Framework_TestCase
{
    /** @var Otp */
    private $otp;

    public function setUp()
    {
        $this->otp = new Otp(
            new NullLogger(),
            new ServerClient(
                new TestHttpClient(),
                'otpServerClient'
            )
        );
    }

    public function testValidOtp()
    {
        $this->otp->verify(
            [
                'username' => 'totp',
                'common_name' => '12345678901234567890123456789012',
                'password' => '123456',
            ]
        );
    }

    /**
     * @expectedException \SURFnet\VPN\Common\HttpClient\Exception\ApiException
     * @expectedExceptionMessage invalid OTP key
     */
    public function testNoInvalidOtpKey()
    {
        $this->otp->verify(
            [
                'username' => 'totp',
                'common_name' => '12345678901234567890123456789012',
                'password' => '654321',
            ]
        );
    }
}
