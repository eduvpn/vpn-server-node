<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2021, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace LC\Node\HttpClient;

class HttpClientResponse
{
    private int $responseCode;

    private string $responseBody;

    public function __construct(int $responseCode, string $responseBody)
    {
        $this->responseCode = $responseCode;
        $this->responseBody = $responseBody;
    }

    public function getCode(): int
    {
        return $this->responseCode;
    }

    public function getBody(): string
    {
        return $this->responseBody;
    }
}
