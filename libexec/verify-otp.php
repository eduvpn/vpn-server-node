#!/usr/bin/env php
<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

require_once dirname(__DIR__).'/vendor/autoload.php';
$baseDir = dirname(__DIR__);

use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\Http\Exception\InputValidationException;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\HttpClient\CurlHttpClient;
use SURFnet\VPN\Common\HttpClient\Exception\ApiException;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\Logger;
use SURFnet\VPN\Node\TwoFactor;

$logger = new Logger(
    basename($argv[0])
);

try {
    $envData = [];
    $envKeys = [
        'INSTANCE_ID',
        'PROFILE_ID',
        'common_name',
        'username',
        'password',
        'auth_control_file', // when "auth-script-openvpn" plugin is used this
                             // field is set to the file we will write the status
                             // to, otherwise return values of this script are used
                             // by OpenVPN
    ];

    // read environment variables
    foreach ($envKeys as $envKey) {
        $envData[$envKey] = getenv($envKey);
    }

    if (false === $instanceId = $envData['INSTANCE_ID']) {
        throw new InputValidationException('invalid "instance_id"');
    }
    $instanceId = InputValidation::instanceId($instanceId);
    $configDir = sprintf('%s/config/%s', $baseDir, $instanceId);
    $config = Config::fromFile(
        sprintf('%s/config.php', $configDir)
    );

    $serverClient = new ServerClient(
        new CurlHttpClient([$config->getItem('apiUser'), $config->getItem('apiPass')]),
        $config->getItem('apiUri')
    );

    $twoFactor = new TwoFactor($logger, $serverClient);
    $twoFactor->verify($envData);

    if (false !== $envData['auth_control_file']) {
        // we were started from the plugin, and not --auth-user-pass-verify
        // '1' indicates success
        @file_put_contents($envData['auth_control_file'], '1');
    }
} catch (ApiException $e) {
    $logger->warning($e->getMessage());
    if (false !== $envData['auth_control_file']) {
        // we were started from the plugin, and not --auth-user-pass-verify
        // '0' indicates failure
        @file_put_contents($envData['auth_control_file'], '0');
    }
    exit(1);
} catch (Exception $e) {
    $logger->error($e->getMessage());
    if (false !== $envData['auth_control_file']) {
        // we were started from the plugin, and not --auth-user-pass-verify
        // '0' indicates failure
        @file_put_contents($envData['auth_control_file'], '0');
    }
    exit(1);
}
