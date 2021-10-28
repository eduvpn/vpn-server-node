<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node;

use LC\Node\HttpClient\HttpClientInterface;
use LC\Node\HttpClient\HttpClientRequest;
use RuntimeException;

class ConfigWriter
{
    private string $openVpnConfigDir;
    private string $wgConfigDir;
    private HttpClientInterface $httpClient;
    private Config $config;

    public function __construct(string $openVpnConfigDir, string $wgConfigDir, HttpClientInterface $httpClient, Config $config)
    {
        $this->openVpnConfigDir = $openVpnConfigDir;
        $this->wgConfigDir = $wgConfigDir;
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function write(): void
    {
        $httpResponse = $this->httpClient->send(
            new HttpClientRequest(
                'POST',
                $this->config->apiUrl().'/server_config',
                [],
                [
                    'node_number' => (string) $this->config->nodeNumber(),
                    'prefer_aes' => $this->config->preferAes() ? 'yes' : 'no',
                    'profile_list' => $this->config->profileList(),
                ]
            )
        );
        if (!$httpResponse->isOkay()) {
            throw new RuntimeException(sprintf('unable to retrieve server_config [HTTP=%d:%s]', $httpResponse->statusCode(), $httpResponse->body()));
        }

        foreach (explode("\r\n", $httpResponse->body()) as $configNameData) {
            [$configName, $configData] = explode(':', $configNameData);

            $configFile = self::getConfigFile($configName);
            $configData = sodium_base642bin($configData, SODIUM_BASE64_VARIANT_ORIGINAL);

            Utils::writeFile($configFile, $configData);
        }
    }

    private function getConfigFile(string $configName): string
    {
        if ('wg.conf' === $configName) {
            return $this->wgConfigDir.'/wg0.conf';
        }

        return $this->openVpnConfigDir.'/'.$configName;
    }
}
