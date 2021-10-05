<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Node\Exception\ConfigException;

class Config
{
    /** @var array */
    protected $configData;

    public function __construct(array $configData)
    {
        $this->configData = $configData;
    }

    public function apiUrl(): string
    {
        if (!\array_key_exists('apiUrl', $this->configData)) {
            throw new ConfigException('key "apiUrl" not available');
        }

        if (!\is_string($this->configData['apiUrl'])) {
            throw new ConfigException('key "apiUrl" not of type string');
        }

        return $this->configData['apiUrl'];
    }

    public static function fromFile(string $configFile): self
    {
        if (!Utils::fileExists($configFile)) {
            throw new ConfigException(sprintf('file "%s" does not exist', $configFile));
        }

        return new self(require $configFile);
    }
}
