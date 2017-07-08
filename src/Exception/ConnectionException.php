<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\Exception;

use Exception;

class ConnectionException extends Exception
{
    /** @var array */
    private $envData;

    public function __construct($message, array $envData, $code = 0, Exception $previous = null)
    {
        $this->envData = $envData;
        parent::__construct($message, $code, $previous);
    }

    public function getEnvData()
    {
        return $this->envData;
    }
}
