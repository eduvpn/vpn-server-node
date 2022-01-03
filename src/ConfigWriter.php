<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
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
        $this->nodeKey = $nodeKey;
    }

    public function write(): void
    {
        $request = new HttpClientRequest(
            'POST',
            $this->config->apiUrl().'/server_config',
            [],
            [
                'public_key' => KeyPair::computePublicKey(Utils::readFile($this->baseDir.'/config/wireguard.key')),
                'prefer_aes' => $this->config->preferAes() ? 'yes' : 'no',
                'profile_id_list' => $this->config->profileIdList(),
            ],
            [
                'X-Node-Number' => (string) $this->config->nodeNumber(),
                'Authorization' => 'Bearer '.$this->nodeKey,
            ]
        );
        $httpResponse = $this->httpClient->send($request->withHttpBuildQuery());
        foreach (explode("\n", $httpResponse->body()) as $configNameData) {
            [$configName, $configData] = explode(':', $configNameData);
            self::writeConfig($configName, Base64::decode($configData));
        }
    }

    private function writeConfig(string $configName, string $configData): void
    {
        if ('wg.conf' === $configName) {
            Utils::writeFile(
                $this->baseDir.'/wg-config/wg0.conf',
                // replace the literal string '{{PRIVATE_KEY}}' with the actual private key of this node
                str_replace('{{PRIVATE_KEY}}', Utils::readFile($this->baseDir.'/config/wireguard.key'), $configData)
            );

            return;
        }

        Utils::writeFile($this->baseDir.'/openvpn-config/'.$configName, $configData);
    }
}
