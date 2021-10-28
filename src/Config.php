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

    public function preferAes(): bool
    {
        if (!\array_key_exists('preferAes', $this->configData)) {
            // determine whether the hardware this code runs on supports
            // hardware AES
            return sodium_crypto_aead_aes256gcm_is_available();
        }

        if (!\is_bool($this->configData['preferAes'])) {
            throw new ConfigException('key "nodeNumber" not of type bool');
        }

        return $this->configData['preferAes'];
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
