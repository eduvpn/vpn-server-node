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
    private $vpnConfigDir;

    private HttpClientInterface $httpClient;

    private string $apiUrl;

    public function __construct(string $vpnConfigDir, HttpClientInterface $httpClient, string $apiUrl)
    {
        $this->vpnConfigDir = $vpnConfigDir;
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
    }

    public function write(): void
    {
        $httpResponse = $this->httpClient->post($this->apiUrl.'/server_config', []);
        foreach (explode("\r\n", $httpResponse->getBody()) as $configNameData) {
            [$configName, $configData] = explode(':', $configNameData);
            if (false === file_put_contents($this->vpnConfigDir.'/'.$configName, sodium_base642bin($configData, \SODIUM_BASE64_VARIANT_ORIGINAL))) {
                throw new RuntimeException('unable to write to "'.$this->vpnConfigDir.'/'.$configName.'"');
            }
        }
    }
}
