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
            return 'http://localhost/vpn-user-portal/node-api.php';
        }

        if (!\is_string($this->configData['apiUrl'])) {
            throw new ConfigException('key "apiUrl" not of type string');
        }

        return $this->configData['apiUrl'];
    }

    public function nodeNumber(): int
    {
        if (!\array_key_exists('nodeNumber', $this->configData)) {
            return 0;
        }

        if (!\is_int($this->configData['nodeNumber'])) {
            throw new ConfigException('key "nodeNumber" not of type int');
        }

        return $this->configData['nodeNumber'];
    }

    /**
     * @return array<string>
     */
    public function profileList(): array
    {
        if (!\array_key_exists('profileList', $this->configData)) {
            return [];
        }

        if ($this->configData['profileList'] !== array_filter($this->configData['profileList'], 'is_string')) {
            throw new ConfigException('key "profileList" not of type array<string>');
        }

        return $this->configData['profileList'];
    }

    public static function fromFile(string $configFile): self
    {
        if (!Utils::fileExists($configFile)) {
            throw new ConfigException(sprintf('file "%s" does not exist', $configFile));
        }

        return new self(require $configFile);
    }
}
