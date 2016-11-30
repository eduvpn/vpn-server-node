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
        // NOTE: if networkCount == 1, then there will be one /64 returned, and not
        // the whole net!
        if (64 <= $this->getPrefix()) {
            throw new IPException('network too small to split up, must be bigger than /64');
        }

        if (0 !== $this->getPrefix() % 4) {
            throw new IPException('network prefix length must be divisible by 4');
        }

        if (pow(2, 64 - $this->getPrefix()) < $networkCount) {
            throw new IPException('network too small to split in this many networks');
        }

        $hexAddress = bin2hex(inet_pton($this->getAddress()));
        // strip the last digits based on prefix size
        $hexAddress = mb_substr($hexAddress, 0, 16 - ((64 - $this->getPrefix()) / 4));

        $splitRanges = [];
        for ($i = 0; $i < $networkCount; ++$i) {
            // pad with zeros until there is enough space for or network number
            $paddedHexAddress = str_pad($hexAddress, 16 - mb_strlen(dechex($i)), '0');
            // append the network number
            $hexAddressWithNetwork = $paddedHexAddress.dechex($i);
            // pad it to the end and convert back to IPv6 address
            $splitRanges[] = new self(sprintf('%s/64', inet_ntop(hex2bin(str_pad($hexAddressWithNetwork, 32, '0')))));
        }

        return $splitRanges;
    }

    public function __toString()
    {
        return $this->getAddressPrefix();
    }

    private function requireIPv4()
    {
        if (4 !== $this->getFamily()) {
            throw new IPException('method only for IPv4');
        }
    }
}
