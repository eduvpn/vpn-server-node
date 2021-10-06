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
use RuntimeException;

class ConfigWriter
{
    private string $openVpnConfigDir;
    private string $wgConfigDir;
    private HttpClientInterface $httpClient;
    private string $apiUrl;

    public function __construct(string $openVpnConfigDir, string $wgConfigDir, HttpClientInterface $httpClient, string $apiUrl)
    {
        $this->openVpnConfigDir = $openVpnConfigDir;
        $this->wgConfigDir = $wgConfigDir;
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
    }

    public function write(): void
    {
        $httpResponse = $this->httpClient->post(
            $this->apiUrl.'/server_config',
            [
                'cpu_has_aes' => sodium_crypto_aead_aes256gcm_is_available() ? 'yes' : 'no',
            ]
        );
        if (200 !== $httpCode = $httpResponse->getCode()) {
            throw new RuntimeException(sprintf('unable to retrieve server_config [HTTP=%d:%s]', $httpCode, $httpResponse->getBody()));
        }
        foreach (explode("\r\n", $httpResponse->getBody()) as $configNameData) {
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
