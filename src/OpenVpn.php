<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2019, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Common\Config;
use LC\Common\FileIO;
use LC\Common\HttpClient\ServerClient;
use LC\Common\ProfileConfig;
use RangeException;
use RuntimeException;

class OpenVpn
{
    // CentOS
    const LIBEXEC_DIR = '/usr/libexec/vpn-server-node';

    const UP_PATH = '/etc/openvpn/up';

    /** @var string */
    private $vpnConfigDir;

    /** @var bool */
    private $useVpnDaemon;

    /**
     * @param string $vpnConfigDir
     * @param bool   $useVpnDaemon
     */
    public function __construct($vpnConfigDir, $useVpnDaemon)
    {
        FileIO::createDir($vpnConfigDir, 0700);
        $this->vpnConfigDir = $vpnConfigDir;
        $this->useVpnDaemon = $useVpnDaemon;
    }

    /**
     * @param string        $vpnUser
     * @param string        $vpnGroup
     * @param array<string> $profileIdDeployList the list of profile IDs to deploy on this node
     *
     * @return void
     */
    public function writeProfiles(ServerClient $serverClient, $vpnUser, $vpnGroup, array $profileIdDeployList)
    {
        $profileList = $serverClient->getRequireArray('profile_list');
        // filter out the profiles we do not want on this node
        if (0 !== \count($profileIdDeployList)) {
            foreach (array_keys($profileList) as $profileId) {
                if (!\in_array($profileId, $profileIdDeployList, true)) {
                    unset($profileList[$profileId]);
                }
            }
        }

        // profileList now contains only the profiles we want to deploy on this
        // node...
        ConfigCheck::verify($profileList);

        foreach ($profileList as $profileId => $profileConfigData) {
            $profileConfig = new ProfileConfig(new Config($profileConfigData));
            // get a server certificate for this profile
            $certData = $serverClient->postRequireArray(
                'add_server_certificate',
                [
                    'profile_id' => $profileId,
                ]
            );

            $this->writeProfile($profileId, $profileConfig, $certData, $vpnUser, $vpnGroup);
        }
    }

    /**
     * @param string $profileId
     * @param string $vpnUser
     * @param string $vpnGroup
     *
     * @return void
     */
    public function writeProfile($profileId, ProfileConfig $profileConfig, array $certData, $vpnUser, $vpnGroup)
    {
        $range = new IP($profileConfig->range());
        $range6 = new IP($profileConfig->range6());
        $processCount = \count($profileConfig->vpnProtoPorts());

        $allowedProcessCount = [1, 2, 4, 8, 16, 32, 64];
        if (!\in_array($processCount, $allowedProcessCount, true)) {
            throw new RuntimeException('"vpnProtoPorts" must contain 1,2,4,8,16,32 or 64 entries');
        }
        $splitRange = $range->split($processCount);
        $splitRange6 = $range6->split($processCount);

        $managementIp = $profileConfig->managementIp();
        if ($this->useVpnDaemon) {
            $managementIp = '127.0.0.1';
        }

        $profileNumber = $profileConfig->profileNumber();

        $processConfig = [
            'managementIp' => $managementIp,
        ];

        for ($i = 0; $i < $processCount; ++$i) {
            list($proto, $port) = self::getProtoPort($profileConfig->vpnProtoPorts(), $profileConfig->listen())[$i];
            $processConfig['range'] = $splitRange[$i];
            $processConfig['range6'] = $splitRange6[$i];
            $processConfig['dev'] = sprintf('tun%d', self::toPort($profileConfig->profileNumber(), $i));
            $processConfig['proto'] = $proto;
            $processConfig['port'] = $port;
            $processConfig['local'] = $profileConfig->listen();
            $processConfig['managementPort'] = 11940 + self::toPort($profileNumber, $i);
            $processConfig['configName'] = sprintf(
                '%s-%d.conf',
                $profileId,
                $i
            );

            $this->writeProcess($profileId, $profileConfig, $processConfig, $certData, $vpnUser, $vpnGroup);
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
     * @param string $profileId
     * @param string $vpnUser
     * @param string $vpnGroup
     *
     * @return void
     */
    private function writeProcess($profileId, ProfileConfig $profileConfig, array $processConfig, array $certData, $vpnUser, $vpnGroup)
    {
        $rangeIp = new IP($processConfig['range']);
        $range6Ip = new IP($processConfig['range6']);

        // static options
        $serverConfig = [
            'verb 3',
            'dev-type tun',
            sprintf('user %s', $vpnUser),
            sprintf('group %s', $vpnGroup),
            'topology subnet',
            'persist-key',
            'persist-tun',
            'remote-cert-tls client',
            'dh none', // Only ECDHE
            'ncp-ciphers AES-256-GCM',  // only AES-256-GCM
            'cipher AES-256-GCM',       // only AES-256-GCM
            // renegotiate data channel key every 10 hours instead of every hour
            sprintf('reneg-sec %d', 10 * 60 * 60),
            sprintf('client-connect %s/client-connect', self::LIBEXEC_DIR),
            sprintf('client-disconnect %s/client-disconnect', self::LIBEXEC_DIR),
            sprintf('server %s %s', $rangeIp->getNetwork(), $rangeIp->getNetmask()),
            sprintf('server-ipv6 %s', $range6Ip->getAddressPrefix()),
            // OpenVPN's pool management does NOT include the last usable IP in
            // the range in the pool, and obviously not the first one as that
            // will be used by OpenVPN itself. So, if you have the range
            // 10.3.240/25 that would give room for 128 - 3 (network,
            // broadcast, OpenVPN) = 125 clients. But OpenVPN thinks
            // differently:
            //
            //      ifconfig_pool_start = 10.3.240.2
            //      ifconfig_pool_end = 10.3.240.125
            //
            // it keeps 10.3.240.126 out of the pool, which is a totally valid
            // address, but alas, won't be available to clients... So we only
            // have *124* possible client IPs to be issued...
            //
            // the same is true for the smallest possible network (/29):
            //      ifconfig_pool_start = 10.3.240.2
            //      ifconfig_pool_end = 10.3.240.5
            //
            // We MUST set max-clients to this number as that will cause a nice
            // timout on the OpenVPN process for the client, until it will try
            // the next available OpenVPN process...
            // @see https://community.openvpn.net/openvpn/ticket/1347
            // @see https://community.openvpn.net/openvpn/ticket/1348
            sprintf('max-clients %d', $rangeIp->getNumberOfHosts() - 2),
            // technically we do NOT need "keepalive" (ping/ping-restart) on
            // TCP, but it seems we do need it to avoid clients disconnecting
            // after 2 minutes of inactivity when the first (previous?) remote
            // was UDP and the default of 120s was set and not properly reset
            // when switching to a TCP remote... This is pure speculation, but
            // having "keepalive" on TCP does keep clients over TCP
            // connected, so it does something at least...
            'keepalive 10 60',
            'script-security 2',
            sprintf('dev %s', $processConfig['dev']),
            sprintf('port %d', $processConfig['port']),
            sprintf('management %s %d', $processConfig['managementIp'], $processConfig['managementPort']),
            sprintf('setenv PROFILE_ID %s', $profileId),
            sprintf('proto %s', $processConfig['proto']),
            sprintf('local %s', $processConfig['local']),
        ];

        if ($profileConfig->tlsOneThree()) {
            // for TLSv1.3 we don't care about the tls-ciphers, they are all
            // fine, let the client choose
            $serverConfig[] = 'tls-version-min 1.3';
        } else {
            $serverConfig[] = 'tls-version-min 1.2';
            $serverConfig[] = 'tls-cipher TLS-ECDHE-RSA-WITH-AES-256-GCM-SHA384:TLS-ECDHE-ECDSA-WITH-AES-256-GCM-SHA384';
        }

        if (!$profileConfig->enableLog()) {
            $serverConfig[] = 'log /dev/null';
        }

        if ('tcp-server' === $processConfig['proto'] || 'tcp6-server' === $processConfig['proto']) {
            $serverConfig[] = 'tcp-nodelay';
        }

        if ('udp' === $processConfig['proto'] || 'udp6' === $processConfig['proto']) {
            // notify the clients to reconnect to the exact same OpenVPN process
            // when the OpenVPN process restarts...
            $serverConfig[] = 'explicit-exit-notify 1';
            // also ask the clients on UDP to tell us when they leave...
            // https://github.com/OpenVPN/openvpn/commit/422ecdac4a2738cd269361e048468d8b58793c4e
            $serverConfig[] = 'push "explicit-exit-notify 1"';
        }

        // Routes
        $serverConfig = array_merge($serverConfig, self::getRoutes($profileConfig));

        // DNS
        $serverConfig = array_merge($serverConfig, self::getDns($rangeIp, $range6Ip, $profileConfig));

        // Client-to-client
        $serverConfig = array_merge($serverConfig, self::getClientToClient($profileConfig));

        // --up
        $serverConfig = array_merge($serverConfig, self::getUp());

        // add Certificates / keys
        $serverConfig[] = '<ca>'.PHP_EOL.$certData['ca'].PHP_EOL.'</ca>';
        $serverConfig[] = '<cert>'.PHP_EOL.$certData['certificate'].PHP_EOL.'</cert>';
        $serverConfig[] = '<key>'.PHP_EOL.$certData['private_key'].PHP_EOL.'</key>';
        $serverConfig[] = '<tls-crypt>'.PHP_EOL.$certData['tls_crypt'].PHP_EOL.'</tls-crypt>';

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
        if ($profileConfig->defaultGateway()) {
            $redirectFlags = ['def1', 'ipv6'];
            if ($profileConfig->blockLan()) {
                $redirectFlags[] = 'block-local';
            }

            $routeConfig[] = sprintf('push "redirect-gateway %s"', implode(' ', $redirectFlags));
        }

        $routeList = $profileConfig->routes();
        if (0 === \count($routeList)) {
            return $routeConfig;
        }

        // Always set a route to the remote host through the client's default
        // gateway to avoid problems when the "split routes" pushed also
        // contain a range with the public IP address of the VPN server.
        // When connecting to a VPN server _over_ IPv6, OpenVPN takes care of
        // this all by itself by setting a /128 through the client's original
        // IPv6 gateway
        $routeConfig[] = 'push "route remote_host 255.255.255.255 net_gateway"';

        // there may be some routes specified, push those, and not the default
        foreach ($routeList as $route) {
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
        if ($profileConfig->defaultGateway()) {
            // prevent DNS leakage on Windows when VPN is default gateway
            $dnsEntries[] = 'push "block-outside-dns"';
        }
        $dnsList = $profileConfig->dns();
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

        // Having multiple "DOMAIN" push messages is NOT officially supported
        // by OpenVPN, but currently used by TunnelKit, and probably others...
        // @see https://github.com/passepartoutvpn/tunnelkit/issues/184
        //
        // If you want to support clients that do NOT yet support
        // DOMAIN-SEARCH, but DO support multiple DOMAIN, you MUST set
        // everything. The configuration below makes it work everywhere
        // (hopefully)...
        //
        //  'dnsSuffix' => ['example.com', 'example.org'],
        //  'dnsDomain' => 'example.com',
        //  'dnsDomainSearch => ['example.org'],
        //
        // This will result in:
        //
        // push "dhcp-option DOMAIN example.com"
        // push "dhcp-option DOMAIN example.org"
        // push "dhcp-option DOMAIN example.com"
        // push "dhcp-option DOMAIN-SEARCH example.org"
        //
        // Windows will take the LAST occurence of DOMAIN and use that as the
        // connection specific suffix. Tunnelkit will take the FIRST occurrence
        // the other DOMAIN items are considered search domains for TunnelKit.
        // Windows will use DOMAIN-SEARCH to set the search domains.
        $dnsSuffixList = $profileConfig->dnsSuffix();
        foreach ($dnsSuffixList as $dnsSuffix) {
            $dnsEntries[] = sprintf('push "dhcp-option DOMAIN %s"', $dnsSuffix);
        }

        // push DOMAIN
        if (null !== $dnsDomain = $profileConfig->dnsDomain()) {
            $dnsEntries[] = sprintf('push "dhcp-option DOMAIN %s"', $dnsDomain);
        }
        // push DOMAIN-SEARCH
        $dnsDomainSearchList = $profileConfig->dnsDomainSearch();
        foreach ($dnsDomainSearchList as $dnsDomainSearch) {
            $dnsEntries[] = sprintf('push "dhcp-option DOMAIN-SEARCH %s"', $dnsDomainSearch);
        }

        return $dnsEntries;
    }

    /**
     * @return array
     */
    private static function getClientToClient(ProfileConfig $profileConfig)
    {
        if (!$profileConfig->clientToClient()) {
            return [];
        }

        $rangeIp = new IP($profileConfig->range());
        $range6Ip = new IP($profileConfig->range6());

        return [
            'client-to-client',
            sprintf('push "route %s %s"', $rangeIp->getAddress(), $rangeIp->getNetmask()),
            sprintf('push "route-ipv6 %s"', $range6Ip->getAddressPrefix()),
        ];
    }

    /**
     * @param int $profileNumber
     * @param int $processNumber
     *
     * @return int
     */
    private static function toPort($profileNumber, $processNumber)
    {
        if (1 > $profileNumber || 64 < $profileNumber) {
            throw new RangeException('1 <= profileNumber <= 64');
        }

        if (0 > $processNumber || 64 <= $processNumber) {
            throw new RangeException('0 <= processNumber < 64');
        }

        // we have 2^16 - 11940 ports available for management ports, so let's
        // say we have 2^14 ports available to distribute over profiles and
        // processes, let's take 12 bits, so we have 64 profiles with each 64
        // processes...
        return ($profileNumber - 1 << 6) | $processNumber;
    }

    /**
     * @return array
     */
    private static function getUp()
    {
        if (!file_exists(self::UP_PATH)) {
            return [];
        }
        if (!is_executable(self::UP_PATH)) {
            return [];
        }

        return [
            'up '.self::UP_PATH,
        ];
    }
}
