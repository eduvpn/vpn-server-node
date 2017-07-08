<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node;

use InvalidArgumentException;
use SURFnet\VPN\Node\Exception\IPException;

class IP
{
    /** @var string */
    private $ipAddress;

    /** @var int */
    private $ipPrefix;

    /** @var int */
    private $ipFamily;

    public function __construct($ipAddressPrefix)
    {
        // detect if there is a prefix
        $hasPrefix = false !== mb_strpos($ipAddressPrefix, '/');
        if ($hasPrefix) {
            list($ipAddress, $ipPrefix) = explode('/', $ipAddressPrefix);
        } else {
            $ipAddress = $ipAddressPrefix;
            $ipPrefix = null;
        }

        // validate the IP address
        if (false === filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new IPException('invalid IP address');
        }

        $is6 = false !== mb_strpos($ipAddress, ':');
        if ($is6) {
            if (is_null($ipPrefix)) {
                $ipPrefix = 128;
            }

            if (!is_numeric($ipPrefix) || 0 > $ipPrefix || 128 < $ipPrefix) {
                throw new IPException('IP prefix must be a number between 0 and 128');
            }
            // normalize the IPv6 address
            $ipAddress = inet_ntop(inet_pton($ipAddress));
        } else {
            if (is_null($ipPrefix)) {
                $ipPrefix = 32;
            }
            if (!is_numeric($ipPrefix) || 0 > $ipPrefix || 32 < $ipPrefix) {
                throw new IPException('IP prefix must be a number between 0 and 32');
            }
        }

        $this->ipAddress = $ipAddress;
        $this->ipPrefix = (int) $ipPrefix;
        $this->ipFamily = $is6 ? 6 : 4;
    }

    public function __toString()
    {
        return $this->getAddressPrefix();
    }

    public function getAddress()
    {
        return $this->ipAddress;
    }

    public function getPrefix()
    {
        return $this->ipPrefix;
    }

    public function getAddressPrefix()
    {
        return sprintf('%s/%d', $this->getAddress(), $this->getPrefix());
    }

    public function getFamily()
    {
        return $this->ipFamily;
    }

    /**
     * IPv4 only.
     */
    public function getNetmask()
    {
        $this->requireIPv4();

        return long2ip(-1 << (32 - $this->getPrefix()));
    }

    /**
     * IPv4 only.
     */
    public function getNetwork()
    {
        $this->requireIPv4();

        return long2ip(ip2long($this->getAddress()) & ip2long($this->getNetmask()));
    }

    /**
     * IPv4 only.
     */
    public function getNumberOfHosts()
    {
        $this->requireIPv4();

        return pow(2, 32 - $this->getPrefix()) - 2;
    }

    public function split($networkCount)
    {
        if (!is_int($networkCount)) {
            throw new InvalidArgumentException('parameter must be integer');
        }

        if (0 !== ($networkCount & ($networkCount - 1))) {
            throw new InvalidArgumentException('parameter must be power of 2');
        }

        if (4 === $this->getFamily()) {
            return $this->split4($networkCount);
        }

        return $this->split6($networkCount);
    }

    private function split4($networkCount)
    {
        if (pow(2, 32 - $this->getPrefix() - 2) < $networkCount) {
            throw new IPException('network too small to split in this many networks');
        }

        $prefix = $this->getPrefix() + log($networkCount, 2);
        $splitRanges = [];
        for ($i = 0; $i < $networkCount; ++$i) {
            $noHosts = pow(2, 32 - $prefix);
            $networkAddress = long2ip($i * $noHosts + ip2long($this->getAddress()));
            $splitRanges[] = new self($networkAddress.'/'.$prefix);
        }

        return $splitRanges;
    }

    private function split6($networkCount)
    {
        if (124 < $this->getPrefix()) {
            throw new IPException('network too small to split up, must be >= /124');
        }

        if (0 !== $this->getPrefix() % 4) {
            throw new IPException('network prefix length must be divisible by 4');
        }

        $hexAddress = bin2hex(inet_pton($this->getAddress()));
        // strip the last digits based on prefix size
        $hexAddress = substr($hexAddress, 0, 32 - ((128 - $this->getPrefix()) / 4));
        $splitRanges = [];
        for ($i = 0; $i < $networkCount; ++$i) {
            $tmpHexAddress = $hexAddress.dechex($i);
            $splitRanges[] = new self(
                sprintf(
                    '%s/%d',
                    inet_ntop(
                        hex2bin(
                            str_pad($tmpHexAddress, 32, '0')
                        )
                    ),
                    $this->getPrefix() + 4
                )
            );
        }

        return $splitRanges;
    }

    private function requireIPv4()
    {
        if (4 !== $this->getFamily()) {
            throw new IPException('method only for IPv4');
        }
    }
}
