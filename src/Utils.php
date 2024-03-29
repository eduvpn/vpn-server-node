<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2023, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node;

use RuntimeException;

class Utils
{
    public static function verifyNodeKey(string $nodeKey): string
    {
        // remove leading/trailing whitespace
        $nodeKey = trim($nodeKey);
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $nodeKey)) {
            throw new RuntimeException('invalid node key, MUST be 64 hex chars');
        }

        return $nodeKey;
    }

    public static function reqEnvString(string $envKey): string
    {
        if (null === $envValue = self::optEnvString($envKey)) {
            throw new RuntimeException('environment variable "'.$envKey.'" not set');
        }

        return $envValue;
    }

    public static function optEnvString(string $envKey): ?string
    {
        if (false === $envValue = getenv($envKey)) {
            return null;
        }

        return $envValue;
    }
}
