<?php

declare(strict_types=1);

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2022, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace Vpn\Node;

interface LogInterface
{
    public function warning(string $logMessage): void;

    public function error(string $logMessage): void;

    public function notice(string $logMessage): void;
}
