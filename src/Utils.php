<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use RuntimeException;

class Utils
{
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

    public static function fileExists(string $fileName): bool
    {
        return @file_exists($fileName);
    }

    public static function writeFile(string $fileName, string $fileContent): void
    {
        if (false === @file_put_contents($fileName, $fileContent)) {
            throw new RuntimeException('unable to write "'.$fileName.'"');
        }
    }

    public static function readFile(string $fileName): string
    {
        if (false === $fileContent = @file_get_contents($fileName)) {
            throw new RuntimeException('unable to read "'.$fileName.'"');
        }

        return $fileContent;
    }
}
