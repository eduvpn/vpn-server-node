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
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Exception\InputValidationException;

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
        try {
            InputValidation::userName($envData['username']);
            $commonName = InputValidation::commonName($envData['common_name']);
            $otpKey = InputValidation::otpKey($envData['password']);
            $userId = self::getUserId($commonName);

            // user has OTP secret registered?
            if (false === $this->serverClient->hasOtpSecret($userId)) {
                $this->logger->error('no OTP secret registered', $envData);

                return false;
            }

            // verify the OTP key
            if (false === $this->serverClient->verifyOtpKey($userId, $otpKey)) {
                $this->logger->error('invalid OTP key', $envData);

                return false;
            }

            $this->logger->info('OTP verified', $envData);

            return true;
        } catch (InputValidationException $e) {
            $this->logger->error($e->getMessage(), $envData);

            return false;
        }
    }

    private static function getUserId($commonName)
    {
        // XXX share this with "Connection" class and possibly others

        // return the part before the first underscore, it is already validated
        // so we can be sure this is fine
        return substr($commonName, 0, strpos($commonName, '_'));
    }
}
