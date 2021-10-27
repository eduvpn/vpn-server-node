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
    private string $apiUrl;
    private int $nodeNumber;

    public function __construct(string $openVpnConfigDir, string $wgConfigDir, HttpClientInterface $httpClient, string $apiUrl, int $nodeNumber)
    {
        $this->openVpnConfigDir = $openVpnConfigDir;
        $this->wgConfigDir = $wgConfigDir;
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->nodeNumber = $nodeNumber;
    }

    public function write(): void
    {
        $httpResponse = $this->httpClient->send(
            new HttpClientRequest(
                'POST',
                $this->apiUrl.'/server_config',
                [],
                [
                    'node_number' => (string) $this->nodeNumber,
                    // XXX allow overriding this flag in config?!
                    'cpu_has_aes' => sodium_crypto_aead_aes256gcm_is_available() ? 'yes' : 'no',
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
