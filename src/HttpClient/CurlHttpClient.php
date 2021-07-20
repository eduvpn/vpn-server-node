<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\HttpClient;

use LC\Node\HttpClient\Exception\HttpClientException;
use RuntimeException;

class CurlHttpClient implements HttpClientInterface
{
    /** @var array<string> */
    private $requestHeaders = [];

    public function __construct(?string $authToken = null)
    {
        if (null !== $authToken) {
            $this->requestHeaders[] = 'Authorization: Bearer '.$authToken;
        }
    }

    /**
     * @param array<string,string|null> $postData
     */
    public function post(string $requestUrl, array $postData): HttpClientResponse
    {
        if (false === $curlChannel = curl_init()) {
            throw new RuntimeException('unable to create cURL channel');
        }

        $curlOptions = [
            \CURLOPT_URL => $requestUrl,
            \CURLOPT_HTTPHEADER => $this->requestHeaders,
            \CURLOPT_POSTFIELDS => http_build_query($postData),
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_CONNECTTIMEOUT => 10,
            \CURLOPT_TIMEOUT => 15,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
        ];

        if (false === curl_setopt_array($curlChannel, $curlOptions)) {
            throw new RuntimeException('unable to set cURL options');
        }

        $responseData = curl_exec($curlChannel);
        if (!\is_string($responseData)) {
            throw new HttpClientException(sprintf('failure performing the HTTP request: "%s"', curl_error($curlChannel)));
        }

        $responseCode = (int) curl_getinfo($curlChannel, \CURLINFO_HTTP_CODE);
        curl_close($curlChannel);

        return new HttpClientResponse(
            $responseCode,
            $responseData
        );
    }
}
