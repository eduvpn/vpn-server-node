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

use Psr\Log\LoggerInterface;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\HttpClient\ServerClient;

class Otp
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(LoggerInterface $logger, ServerClient $serverClient)
    {
        $this->logger = $logger;
        $this->serverClient = $serverClient;
    }

    public function verify(array $envData)
    {
        $otpType = InputValidation::otpType($envData['username']);
        $commonName = InputValidation::commonName($envData['common_name']);
        $otpKey = InputValidation::otpKey($envData['password']);

        $this->serverClient->verifyOtp($commonName, $otpType, $otpKey);
    }
}
