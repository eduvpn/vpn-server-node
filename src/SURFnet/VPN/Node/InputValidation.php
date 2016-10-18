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

use SURFnet\VPN\Node\Exception\InputValidationException;

class InputValidation
{
    public static function instanceId($instanceId)
    {
        if (0 === preg_match('/^[a-zA-Z0-9-\.]+$/', $instanceId)) {
            throw new InputValidationException('invalid instanceId pattern');
        }
        // MUST not be '..'
        if ('..' === $instanceId) {
            throw new InputValidationException('invalid instanceId pattern, cannot be ".."');
        }

        return $instanceId;
    }

    public static function poolId($poolId)
    {
        if (0 === preg_match('/^[a-zA-Z0-9]{2,}$/', $poolId)) {
            throw new InputValidationException('invalid poolId pattern');
        }

        return $poolId;
    }

    public static function userName($userName)
    {
        if ('totp' !== $userName) {
            throw new InputValidationException('invalid userName');
        }

        return $userName;
    }

    public static function commonName($commonName)
    {
        // the commonName consists of a userId and a configName seperated by an
        // underscore, needs to be at least of length 3
        if (0 === preg_match('/^[a-zA-Z0-9-_.@]{3,}$/', $commonName)) {
            throw new InputValidationException('invalid commonName pattern');
        }
        if (false === strpos($commonName, '_')) {
            throw new InputValidationException('invalid commonName pattern, requires underscore');
        }
        if (strlen($commonName) - 1 === strrpos($commonName, '_')) {
            throw new InputValidationException('invalid commonName pattern, cannot end with underscore');
        }

        return $commonName;
    }

    public static function otpKey($otpKey)
    {
        // the otpKey consists of 6 digits
        if (0 === preg_match('/^[0-9]{6}$/', $otpKey)) {
            throw new InputValidationException('invalid otpKey pattern');
        }

        return $otpKey;
    }
}
