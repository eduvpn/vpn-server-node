<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */
$baseDir = dirname(__DIR__);

// find the autoloader (package installs, composer)
foreach (['src', 'vendor'] as $autoloadDir) {
    if (@file_exists(sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir))) {
        require_once sprintf('%s/%s/autoload.php', $baseDir, $autoloadDir);
        break;
    }
}

use SURFnet\VPN\Common\Config;
use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\Http\BasicAuthenticationHook;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Response;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Logger;
use SURFnet\VPN\Node\Api\OpenVpnModule;
use SURFnet\VPN\Node\OpenVpn\ManagementSocket;
use SURFnet\VPN\Node\OpenVpn\ServerManager;

$logger = new Logger('vpn-server-node');

try {
    $request = new Request($_SERVER, $_GET, $_POST);

    if (false === $instanceId = getenv('VPN_INSTANCE_ID')) {
        $instanceId = $request->getServerName();
    }

    $configDir = sprintf('%s/config/%s', $baseDir, $instanceId);
    $dataDir = sprintf('%s/data/%s', $baseDir, $instanceId);

    $config = Config::fromFile(
        sprintf('%s/config.php', $configDir)
    );

    $basicAuthentication = new BasicAuthenticationHook(
        $config->getSection('apiConsumers')->toArray(),
        'vpn-server-node'
    );

    $service = new Service();
    $service->addBeforeHook('auth', $basicAuthentication);

    // XXX be more sure we get something useful here...
    $portMapping = @unserialize(
        FileIO::readFile(
            sprintf('%s/mapping.dat', $dataDir)
        ),
        [
            'allowed_classes' => false,
        ]
    );

    $service->addModule(
        new OpenVpnModule(
            new ServerManager($portMapping, new ManagementSocket(), $logger)
        )
    );

    $service->run($request)->send();
} catch (Exception $e) {
    $logger->error($e->getMessage());
    $response = new Response(500, 'application/json');
    $response->setBody(json_encode(['error' => $e->getMessage()]));
    $response->send();
}
