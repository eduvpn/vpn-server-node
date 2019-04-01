<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\Exception;

use Exception;

class ConnectionException extends Exception
{
    /** @var array */
    private $envData;

    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, array $envData, $code = 0, Exception $previous = null)
    {
        $this->envData = $envData;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getEnvData()
    {
        return $this->envData;
    }
}
