<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2023, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node\HttpClient;

interface HttpClientInterface
{
    public function send(HttpClientRequest $httpClientRequest): HttpClientResponse;
}
