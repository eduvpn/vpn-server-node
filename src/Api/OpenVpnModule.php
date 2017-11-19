<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\Api;

use SURFnet\VPN\Common\Http\ApiResponse;
use SURFnet\VPN\Common\Http\AuthUtils;
use SURFnet\VPN\Common\Http\InputValidation;
use SURFnet\VPN\Common\Http\Request;
use SURFnet\VPN\Common\Http\Service;
use SURFnet\VPN\Common\Http\ServiceModuleInterface;
use SURFnet\VPN\Node\OpenVpn\ServerManager;

class OpenVpnModule implements ServiceModuleInterface
{
    /** @var \SURFnet\VPN\Node\OpenVpn\ServerManager */
    private $serverManager;

    public function __construct(ServerManager $serverManager)
    {
        $this->serverManager = $serverManager;
    }

    public function init(Service $service)
    {
        $service->get(
            '/client_connections',
            function (Request $request, array $hookData) {
                AuthUtils::requireUser($hookData, ['vpn-admin-portal']);

                return new ApiResponse('client_connections', $this->serverManager->connections());
            }
        );

        $service->post(
            '/kill_client',
            function (Request $request, array $hookData) {
                AuthUtils::requireUser($hookData, ['vpn-admin-portal']);

                $commonName = InputValidation::commonName($request->getPostParameter('common_name'));

                return new ApiResponse('kill_client', $this->serverManager->kill($commonName));
            }
        );
    }
}
