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

    public function optionalString(string $k): ?string
    {
        if (!\array_key_exists($k, $this->configData)) {
            return null;
        }
        if (!\is_string($this->configData[$k])) {
            throw new ConfigException('key "'.$k.'" not of type string');
        }

        return $this->configData[$k];
    }

    public function requireString(string $k, ?string $d = null): string
    {
        if (null === $v = $this->optionalString($k)) {
            if (null !== $d) {
                return $d;
            }

            throw new ConfigException('key "'.$k.'" not available');
        }

        return $v;
    }

    public function optionalArray(string $k): ?array
    {
        if (!\array_key_exists($k, $this->configData)) {
            return null;
        }
        $configValue = $this->configData[$k];
        if (!\is_array($configValue)) {
            throw new ConfigException('key "'.$k.'" not of type array');
        }

        return $configValue;
    }

    public function requireArray(string $k, array $d = null): array
    {
        if (null === $v = $this->optionalArray($k)) {
            if (null !== $d) {
                return $d;
            }

            throw new ConfigException('key "'.$k.'" not available');
        }

        return $v;
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public static function fromFile(string $configFile): self
    {
        if (false === file_exists($configFile)) {
            throw new ConfigException(sprintf('unable to read "%s"', $configFile));
        }

        return new self(require $configFile);
    }
}
