<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node;

use Psr\Log\LoggerInterface;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\HttpClient\ServerClient;

class TwoFactor
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
        $otpType = InputValidation::twoFactorType($envData['username']);
        $commonName = InputValidation::commonName($envData['common_name']);
        $otpValue = InputValidation::twoFactorValue($envData['password']);

        $this->serverClient->post('verify_two_factor', ['common_name' => $commonName, 'two_factor_type' => $otpType, 'two_factor_value' => $otpValue]);
    }
}
