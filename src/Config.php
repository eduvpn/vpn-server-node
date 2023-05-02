<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2023, The Commons Conservancy eduVPN Programme
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
     * User to use to run the OpenVPN server process.
     */
    public function vpnUser(): string
    {
        if (null !== $vpnUser = $this->optionalString('vpnUser')) {
            return $vpnUser;
        }

        // rudimentary OS detection
        if (FileIO::exists('/etc/debian_version')) {
            // Debian & Ubuntu
            return 'nobody';
        }

        return 'openvpn';
    }

    /**
     * Group to use to run the OpenVPN server process.
     */
    public function vpnGroup(): string
    {
        if (null !== $vpnGroup = $this->optionalString('vpnGroup')) {
            return $vpnGroup;
        }

        // rudimentary OS detection
        if (FileIO::exists('/etc/debian_version')) {
            // Debian & Ubuntu
            return 'nogroup';
        }

        return 'openvpn';
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public static function fromFile(string $configFile): self
    {
        if (false === FileIO::exists($configFile)) {
            throw new ConfigException(sprintf('unable to read "%s"', $configFile));
        }

        return new self(require $configFile);
    }
}
