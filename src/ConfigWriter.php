<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2023, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node;

use Vpn\Node\HttpClient\HttpClientInterface;
use Vpn\Node\HttpClient\HttpClientRequest;

class ConfigWriter
{
    private string $baseDir;
    private HttpClientInterface $httpClient;
    private Config $config;
    private string $nodeKey;

    public function __construct(string $baseDir, HttpClientInterface $httpClient, Config $config, string $nodeKey)
    {
        $this->baseDir = $baseDir;
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->nodeKey = Utils::verifyNodeKey($nodeKey);
    }

    public function write(): void
    {
        $wgKeyFile = $this->baseDir.'/config/keys/wireguard.key';
        $request = new HttpClientRequest(
            'POST',
            $this->config->apiUrl().'/server_config',
            [],
            [
                'public_key' => KeyPair::computePublicKey(FileIO::read($wgKeyFile)),
                'prefer_aes' => $this->config->preferAes() ? 'yes' : 'no',
                'profile_id_list' => $this->config->profileIdList(),
                'vpn_user' => $this->config->vpnUser(),
                'vpn_group' => $this->config->vpnGroup(),
            ],
            [
                'X-Node-Number' => (string) $this->config->nodeNumber(),
                'Authorization' => 'Bearer '.$this->nodeKey,
            ]
        );
        $httpResponse = $this->httpClient->send($request->withHttpBuildQuery());
        $serverConfigList = Json::decode($httpResponse->body());
        foreach ($serverConfigList as $configName => $configData) {
            self::writeConfig($configName, $configData);
        }
    }

    private function writeConfig(string $configName, string $configData): void
    {
        $wgKeyFile = $this->baseDir.'/config/keys/wireguard.key';
        if ('wg.conf' === $configName) {
            FileIO::write(
                $this->baseDir.'/wg-config/wg0.conf',
                // replace the literal string '{{PRIVATE_KEY}}' with the actual private key of this node
                str_replace('{{PRIVATE_KEY}}', FileIO::read($wgKeyFile), $configData)
            );

            return;
        }

        FileIO::write($this->baseDir.'/openvpn-config/'.$configName, $configData);
    }
}
