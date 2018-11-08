<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node;

use DateTime;
use DateTimeZone;
use RuntimeException;
use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\ProfileConfig;

class OpenVpn
{
    // CentOS
    const LIBEXEC_DIR = '/usr/libexec/vpn-server-node';

    /** @var string */
    private $vpnConfigDir;

    /**
     * @param string $vpnConfigDir
     */
    public function __construct($vpnConfigDir)
    {
        FileIO::createDir($vpnConfigDir, 0700);
        $this->vpnConfigDir = $vpnConfigDir;
    }

    /**
     * @param string $vpnTlsDir
     * @param string $commonName
     *
     * @return void
     */
    public function generateKeys(ServerClient $serverClient, $vpnTlsDir, $commonName)
    {
        FileIO::createDir($vpnTlsDir, 0700);
        $certData = $serverClient->postRequireArray('add_server_certificate', ['common_name' => $commonName]);

        $certFileMapping = [
            'ca' => sprintf('%s/ca.crt', $vpnTlsDir),
            'certificate' => sprintf('%s/server.crt', $vpnTlsDir),
            'private_key' => sprintf('%s/server.key', $vpnTlsDir),
            'ta' => sprintf('%s/ta.key', $vpnTlsDir),
        ];

        foreach ($certFileMapping as $k => $v) {
            FileIO::writeFile($v, $certData[$k], 0600);
        }
    }

    /**
     * @param string $instanceId
     * @param string $vpnUser
     * @param string $vpnGroup
     *
     * @return void
     */
    public function writeProfiles(ServerClient $serverClient, $instanceId, $vpnUser, $vpnGroup)
    {
        $instanceNumber = $serverClient->getRequireInt('instance_number');
        $profileList = $serverClient->getRequireArray('profile_list');

        $profileIdList = array_keys($profileList);
        foreach ($profileIdList as $profileId) {
            $profileConfigData = $profileList[$profileId];
            $profileConfigData['_user'] = $vpnUser;
            $profileConfigData['_group'] = $vpnGroup;
            $profileConfig = new ProfileConfig($profileConfigData);
            $this->writeProfile($instanceNumber, $instanceId, $profileId, $profileConfig);

            // generate a CN based on date and profile, instance
            $dateTime = new DateTime('now', new DateTimeZone('UTC'));
            $dateString = $dateTime->format('YmdHis');
            $cn = sprintf('%s.%s.%s', $dateString, $profileId, $instanceId);
            $vpnTlsDir = sprintf('%s/tls/%s/%s', $this->vpnConfigDir, $instanceId, $profileId);

            $this->generateKeys($serverClient, $vpnTlsDir, $cn);
        }
    }

    /**
     * @param int    $instanceNumber
     * @param string $instanceId
     * @param string $profileId
     *
     * @return void
     */
    public function writeProfile($instanceNumber, $instanceId, $profileId, ProfileConfig $profileConfig)
    {
        $range = new IP($profileConfig->getItem('range'));
        $range6 = new IP($profileConfig->getItem('range6'));
        $processCount = \count($profileConfig->getItem('vpnProtoPorts'));

        $splitRange = $range->split($processCount);
        $splitRange6 = $range6->split($processCount);

        $managementIp = $profileConfig->getItem('managementIp');
        $profileNumber = $profileConfig->getItem('profileNumber');

        $processConfig = [
            'managementIp' => $managementIp,
        ];

        for ($i = 0; $i < $processCount; ++$i) {
            list($proto, $port) = self::getProtoPort($profileConfig->getItem('vpnProtoPorts'), $profileConfig->getItem('listen'))[$i];
            $processConfig['range'] = $splitRange[$i];
            $processConfig['range6'] = $splitRange6[$i];
            $processConfig['dev'] = sprintf('tun-%d-%d-%d', $instanceNumber, $profileConfig->getItem('profileNumber'), $i);
            $processConfig['proto'] = $proto;
            $processConfig['port'] = $port;
            $processConfig['local'] = $profileConfig->getItem('listen');
            $processConfig['managementPort'] = 11940 + $this->toPort($instanceNumber, $profileNumber, $i);
            $processConfig['configName'] = sprintf(
                '%s-%s-%d.conf',
                $instanceId,
                $profileId,
                $i
            );

            $this->writeProcess($instanceId, $profileId, $profileConfig, $processConfig);
        }
    }

    /**
     * @param string $listenAddress
     * @param string $proto
     *
     * @return string
     */
    private static function getFamilyProto($listenAddress, $proto)
    {
        $v6 = false !== strpos($listenAddress, ':');
        if ('udp' === $proto) {
            return $v6 ? 'udp6' : 'udp';
        }
        if ('tcp' === $proto) {
            return $v6 ? 'tcp6-server' : 'tcp-server';
        }

        throw new RuntimeException('only "tcp" and "udp" are supported as protocols');
    }

    /**
     * @param string $listenAddress
     *
     * @return array
     */
    private static function getProtoPort(array $vpnProcesses, $listenAddress)
    {
        $convertedPortProto = [];

        foreach ($vpnProcesses as $vpnProcess) {
            list($proto, $port) = explode('/', $vpnProcess);
            $convertedPortProto[] = [self::getFamilyProto($listenAddress, $proto), $port];
        }

        return $convertedPortProto;
    }

    /**
     * @param string $instanceId
     * @param string $profileId
     *
     * @return void
     */
    private function writeProcess($instanceId, $profileId, ProfileConfig $profileConfig, array $processConfig)
    {
        $tlsDir = sprintf('tls/%s/%s', $instanceId, $profileId);

        $rangeIp = new IP($processConfig['range']);
        $range6Ip = new IP($processConfig['range6']);

        // static options
        $serverConfig = [
            'verb 3',
            'dev-type tun',
            sprintf('user %s', $profileConfig->getItem('_user')),
            sprintf('group %s', $profileConfig->getItem('_group')),
            'topology subnet',
            'persist-key',
            'persist-tun',
            'remote-cert-tls client',
            'tls-version-min 1.2',
            'tls-cipher TLS-ECDHE-RSA-WITH-AES-256-GCM-SHA384',
            'dh none', // Only ECDHE
            'ncp-ciphers AES-256-GCM',  // only AES-256-GCM
            'cipher AES-256-GCM',       // only AES-256-GCM
            sprintf('client-connect %s/client-connect', self::LIBEXEC_DIR),
            sprintf('client-disconnect %s/client-disconnect', self::LIBEXEC_DIR),
            sprintf('ca %s/ca.crt', $tlsDir),
            sprintf('cert %s/server.crt', $tlsDir),
            sprintf('key %s/server.key', $tlsDir),
            sprintf('server %s %s', $rangeIp->getNetwork(), $rangeIp->getNetmask()),
            sprintf('server-ipv6 %s', $range6Ip->getAddressPrefix()),
            sprintf('max-clients %d', $rangeIp->getNumberOfHosts() - 1),
            sprintf('script-security %d', $profileConfig->getItem('twoFactor') ? 3 : 2),
            sprintf('dev %s', $processConfig['dev']),
            sprintf('port %d', $processConfig['port']),
            sprintf('management %s %d', $processConfig['managementIp'], $processConfig['managementPort']),
            sprintf('setenv INSTANCE_ID %s', $instanceId),
            sprintf('setenv PROFILE_ID %s', $profileId),
            sprintf('proto %s', $processConfig['proto']),
            sprintf('local %s', $processConfig['local']),
        ];

        if ($profileConfig->getItem('enableCompression')) {
            // this only enables compression framing... it tells the clients
            // to NOT use compression, as that is insecure, see e.g. "VORACLE"
            // attack
            $serverConfig[] = 'comp-lzo no';
            $serverConfig[] = 'push "comp-lzo no"';
        }

        if (!$profileConfig->getItem('enableLog')) {
            $serverConfig[] = 'log /dev/null';
        }

        if ('tcp-server' === $processConfig['proto'] || 'tcp6-server' === $processConfig['proto']) {
            $serverConfig[] = 'tcp-nodelay';
        }

        if ('udp' === $processConfig['proto'] || 'udp6' === $processConfig['proto']) {
            // notify the clients to reconnect to the exact same OpenVPN process
            // when the OpenVPN process restarts...
            $serverConfig[] = 'keepalive 25 150';
            $serverConfig[] = 'explicit-exit-notify 1';
            // also ask the clients on UDP to tell us when they leave...
            // https://github.com/OpenVPN/openvpn/commit/422ecdac4a2738cd269361e048468d8b58793c4e
            $serverConfig[] = 'push "explicit-exit-notify 1"';
        }

        if ($profileConfig->getItem('twoFactor')) {
            $serverConfig[] = sprintf('auth-gen-token %d', 60 * 60 * 12);  // Added in OpenVPN 2.4

            // detector for https://github.com/fac/auth-script-openvpn,
            // use it if it is there
            $usePlugin = false;
            $pluginPathList = [
                '/usr/lib64/openvpn/plugins/openvpn-plugin-auth-script.so', // CentOS / Fedora (64 bit)
                '/usr/lib/openvpn/plugins/openvpn-plugin-auth-script.so',   // CentOS / Fedora (32 bit)
                '/usr/lib/openvpn/openvpn-plugin-auth-script.so',           // Debian / Ubuntu
            ];
            foreach ($pluginPathList as $pluginPath) {
                if (@file_exists($pluginPath)) {
                    $usePlugin = $pluginPath;
                    break;
                }
            }
            if (false !== $usePlugin) {
                $serverConfig[] = sprintf('plugin %s %s/verify-otp', $usePlugin, self::LIBEXEC_DIR);
            } else {
                $serverConfig[] = sprintf('auth-user-pass-verify %s/verify-otp via-env', self::LIBEXEC_DIR);
            }
        }

        if ('tls-crypt' === self::getTlsProtection($profileConfig)) {
            $serverConfig[] = sprintf('tls-crypt %s/ta.key', $tlsDir);
        }
        if ('tls-auth' === self::getTlsProtection($profileConfig)) {
            // only tls-auth needs "auth", AES-256-GCM no longer requires it
            $serverConfig[] = 'auth SHA256';
            $serverConfig[] = sprintf('tls-auth %s/ta.key 0', $tlsDir);
        } else {
            // we do not require "auth"
            $serverConfig[] = 'auth none';
        }

        // Routes
        $serverConfig = array_merge($serverConfig, self::getRoutes($profileConfig));

        // DNS
        $serverConfig = array_merge($serverConfig, self::getDns($rangeIp, $range6Ip, $profileConfig));

        // Client-to-client
        $serverConfig = array_merge($serverConfig, self::getClientToClient($profileConfig));

        sort($serverConfig, SORT_STRING);

        $serverConfig = array_merge(
            [
                '#',
                '# OpenVPN Server Configuration',
                '#',
                '# ******************************************',
                '# * THIS FILE IS GENERATED, DO NOT MODIFY! *',
                '# ******************************************',
                '#',
            ],
            $serverConfig
        );

        $configFile = sprintf('%s/%s', $this->vpnConfigDir, $processConfig['configName']);

        FileIO::writeFile($configFile, implode(PHP_EOL, $serverConfig), 0600);
    }

    /**
     * @return array
     */
    private static function getRoutes(ProfileConfig $profileConfig)
    {
        if ($profileConfig->getItem('defaultGateway')) {
            $redirectFlags = ['def1', 'ipv6'];
            if ($profileConfig->hasItem('blockLan') && $profileConfig->getItem('blockLan')) {
                $redirectFlags[] = 'block-local';
            }

            return [
                sprintf('push "redirect-gateway %s"', implode(' ', $redirectFlags)),
            ];
        }

        $routeConfig = [];
        // there may be some routes specified, push those, and not the default
        foreach ($profileConfig->getSection('routes')->toArray() as $route) {
            $routeIp = new IP($route);
            if (6 === $routeIp->getFamily()) {
                // IPv6
                $routeConfig[] = sprintf('push "route-ipv6 %s"', $routeIp->getAddressPrefix());
            } else {
                // IPv4
                $routeConfig[] = sprintf('push "route %s %s"', $routeIp->getAddress(), $routeIp->getNetmask());
            }
        }

        return $routeConfig;
    }

    /**
     * @return array
     */
    private static function getDns(IP $rangeIp, IP $range6Ip, ProfileConfig $profileConfig)
    {
        $dnsEntries = [];
        if ($profileConfig->getItem('defaultGateway')) {
            // prevent DNS leakage on Windows when VPN is default gateway
            $dnsEntries[] = 'push "block-outside-dns"';
        }
        $dnsList = $profileConfig->getSection('dns')->toArray();
        foreach ($dnsList as $dnsAddress) {
            // replace the macros by IP addresses (LOCAL_DNS)
            if ('@GW4@' === $dnsAddress) {
                $dnsAddress = $rangeIp->getFirstHost();
            }
            if ('@GW6@' === $dnsAddress) {
                $dnsAddress = $range6Ip->getFirstHost();
            }
            $dnsEntries[] = sprintf('push "dhcp-option DNS %s"', $dnsAddress);
        }

        return $dnsEntries;
    }

    /**
     * @return array
     */
    private static function getClientToClient(ProfileConfig $profileConfig)
    {
        if (!$profileConfig->getItem('clientToClient')) {
            return [];
        }

        $rangeIp = new IP($profileConfig->getItem('range'));
        $range6Ip = new IP($profileConfig->getItem('range6'));

        return [
            'client-to-client',
            sprintf('push "route %s %s"', $rangeIp->getAddress(), $rangeIp->getNetmask()),
            sprintf('push "route-ipv6 %s"', $range6Ip->getAddressPrefix()),
        ];
    }

    /**
     * @param int $instanceNumber
     * @param int $profileNumber
     * @param int $processNumber
     *
     * @return int
     */
    private function toPort($instanceNumber, $profileNumber, $processNumber)
    {
        // convert an instanceNumber, $profileNumber and $processNumber to a management port

        // instanceId = 6 bits (max 64)
        // profileNumber = 4 bits (max 16)
        // processNumber = 4 bits  (max 16)
        return ($instanceNumber - 1 << 8) | ($profileNumber - 1 << 4) | ($processNumber);
    }

    /**
     * @param \SURFnet\VPN\Common\ProfileConfig $profileConfig
     *
     * @return false|string
     */
    private static function getTlsProtection(ProfileConfig $profileConfig)
    {
        // if tlsCrypt is there, it is leading
        if ($profileConfig->hasItem('tlsCrypt')) {
            if ($profileConfig->getItem('tlsCrypt')) {
                return 'tls-crypt';
            }

            return 'tls-auth';
        }

        // if we reach this point, tlsCrypt is not specified in configuration
        // file. This either means we have a new configuration where only
        // "tlsProtection" is set, GOOD! Or we have a configuration where
        // neither is set, which is strange as "tlsCrypt" WAS there >= 1.0.0.
        // So offically we do not support this anyway, but I found some
        // machines in the wild that didn't have tlsCrypt yet. The old default
        // when tlsCrypt was missing was "false" which meant "use tls-auth".
        // That is why the new default value for "tlsProtection" MUST be
        // "tls-auth". The configuration file will set it to "tls-crypt"
        // anyway, so that should be fine.

        return $profileConfig->getItem('tlsProtection');
    }
}
