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

    // https://github.com/fac/auth-script-openvpn
    const AUTH_SCRIPT_OPENVPN = '/usr/lib64/openvpn/plugins/auth_script.so';

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
        $certData = $serverClient->post('add_server_certificate', ['common_name' => $commonName]);

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
     * @param bool   $generateCerts
     *
     * @return void
     */
    public function writeProfiles(ServerClient $serverClient, $instanceId, $vpnUser, $vpnGroup, $generateCerts)
    {
        $instanceNumber = $serverClient->get('instance_number');
        $profileList = $serverClient->get('profile_list');

        $profileIdList = array_keys($profileList);
        foreach ($profileIdList as $profileId) {
            $profileConfigData = $profileList[$profileId];
            $profileConfigData['_user'] = $vpnUser;
            $profileConfigData['_group'] = $vpnGroup;
            $profileConfig = new ProfileConfig($profileConfigData);
            $this->writeProfile($instanceNumber, $instanceId, $profileId, $profileConfig);
            if ($generateCerts) {
                // generate a CN based on date and profile, instance
                $dateTime = new DateTime('now', new DateTimeZone('UTC'));
                $dateString = $dateTime->format('YmdHis');
                $cn = sprintf('%s.%s.%s', $dateString, $profileId, $instanceId);
                $vpnTlsDir = sprintf('%s/tls/%s/%s', $this->vpnConfigDir, $instanceId, $profileId);

                $this->generateKeys($serverClient, $vpnTlsDir, $cn);
            }
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
        $processCount = count($profileConfig->getItem('vpnProtoPorts'));

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
            'auth SHA256',
            'dh none', // Only ECDHE
            'ncp-ciphers AES-256-GCM', // force AES-256-GCM for >= 2.4 clients
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

        // force AES-256-GCM when we only support 2.4 clients
        // tlsCrypt is only supported on 2.4 clients
        if ($profileConfig->getItem('tlsCrypt')) {
            // 2.4 only
            $serverConfig[] = 'cipher AES-256-GCM';
        } else {
            // 2.3 & 2.4
            $serverConfig[] = 'cipher AES-256-CBC';
        }

        if ($profileConfig->getItem('enableCompression')) {
            // we cannot switch to "--compress", it breaks clients for some
            // reason even if not using compression, it seems the framing is
            // different?
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
            // notify the clients to reconnect when restarting OpenVPN on the server
            // OpenVPN server >= 2.4
            $serverConfig[] = 'keepalive 20 120';
            $serverConfig[] = 'explicit-exit-notify 1';
            // also ask the clients on UDP to tell us when they leave...
            // https://github.com/OpenVPN/openvpn/commit/422ecdac4a2738cd269361e048468d8b58793c4e
            $serverConfig[] = 'push "explicit-exit-notify 1"';
        }

        if ($profileConfig->getItem('twoFactor')) {
            $serverConfig[] = sprintf('auth-gen-token %d', 60 * 60 * 8);  // Added in OpenVPN 2.4

            // detector for https://github.com/fac/auth-script-openvpn, use it if it is there
            if (@file_exists(self::AUTH_SCRIPT_OPENVPN)) {
                $serverConfig[] = sprintf('plugin %s %s/verify-otp', self::AUTH_SCRIPT_OPENVPN, self::LIBEXEC_DIR);
            } else {
                $serverConfig[] = sprintf('auth-user-pass-verify %s/verify-otp via-env', self::LIBEXEC_DIR);
            }
        }

        if ($profileConfig->getItem('tlsCrypt')) {
            $serverConfig[] = sprintf('tls-crypt %s/ta.key', $tlsDir);
        } else {
            $serverConfig[] = sprintf('tls-auth %s/ta.key 0', $tlsDir);
        }

        // Routes
        $serverConfig = array_merge($serverConfig, self::getRoutes($profileConfig));

        // DNS
        $serverConfig = array_merge($serverConfig, self::getDns($profileConfig));

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
        $routeConfig = [];
        if ($profileConfig->getItem('defaultGateway')) {
            $routeConfig[] = 'push "redirect-gateway def1 bypass-dhcp ipv6"';

            if (!$profileConfig->getItem('tlsCrypt')) {
                // tlsCrypt is only supported on 2.4 clients, so if we don't
                // support tlsCrypt we assume 2.3 client compat
                $routeConfig[] = 'push "route-ipv6 2000::/4"';
                $routeConfig[] = 'push "route-ipv6 3000::/4"';
            }

            return $routeConfig;
        }

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
    private static function getDns(ProfileConfig $profileConfig)
    {
        // only push DNS if we are the default route
        if (!$profileConfig->getItem('defaultGateway')) {
            return [];
        }

        $dnsEntries = [];
        foreach ($profileConfig->getSection('dns')->toArray() as $dnsAddress) {
            // also add DNS6 for OpenVPN >= 2.4beta2
            if (false !== strpos($dnsAddress, ':')) {
                $dnsEntries[] = sprintf('push "dhcp-option DNS6 %s"', $dnsAddress);
                continue;
            }
            $dnsEntries[] = sprintf('push "dhcp-option DNS %s"', $dnsAddress);
        }

        // prevent DNS leakage on Windows
        $dnsEntries[] = 'push "block-outside-dns"';

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
}
