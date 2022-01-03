<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2022, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node;

use Vpn\Node\Exception\ConfigException;

class Config
{
    use ConfigTrait;

    private array $configData;

    public function __construct(array $configData)
    {
        $this->configData = $configData;
    }

    public function apiUrl(): string
    {
        return $this->requireString('apiUrl', 'http://localhost/vpn-user-portal/node-api.php');
    }

    public function nodeNumber(): int
    {
        return $this->requireInt('nodeNumber', 0);
    }

    public function preferAes(): bool
    {
        return $this->requireBool('preferAes', sodium_crypto_aead_aes256gcm_is_available());
    }

    /**
     * @return array<string>
     */
    public function profileIdList(): array
    {
        return $this->requireStringArray('profileIdList', []);
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public static function fromFile(string $configFile): self
    {
        if (false === Utils::fileExists($configFile)) {
            throw new ConfigException(sprintf('unable to read "%s"', $configFile));
        }

        return new self(require $configFile);
    }
}
