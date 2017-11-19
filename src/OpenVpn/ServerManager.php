<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\OpenVpn;

use Psr\Log\LoggerInterface;
use SURFnet\VPN\Node\OpenVpn\Exception\ManagementSocketException;

/**
 * Manage all OpenVPN processes controlled by this service.
 */
class ServerManager
{
    /** @var array list of profiles and their associated management ports */
    private $portMapping;

    /** @var ManagementSocketInterface */
    private $managementSocket;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(array $portMapping, ManagementSocketInterface $managementSocket, LoggerInterface $logger)
    {
        $this->portMapping = $portMapping;
        $this->managementSocket = $managementSocket;
        $this->logger = $logger;
    }

    /**
     * Get the connection information about connected clients.
     */
    public function connections()
    {
        $clientConnections = [];
        foreach (array_keys($this->portMapping) as $profileId) {
            $profileConnections = [];
            foreach ($this->portMapping[$profileId]['managementPortList'] as $managementPort) {
                try {
                    // open the socket connection
                    $this->managementSocket->open(
                        sprintf('tcp://127.0.0.1:%d', $managementPort)
                    );

                    $profileConnections = array_merge(
                        $profileConnections,
                        StatusParser::parse($this->managementSocket->command('status 2'))
                    );
                    // close the socket connection
                    $this->managementSocket->close();
                } catch (ManagementSocketException $e) {
                    // we log the error, but continue with the next management port
                    $this->logger->error(
                        sprintf(
                            'tcp://127.0.0.1:%d: %s',
                            $managementPort,
                            $e->getMessage()
                        )
                    );
                }
            }
            // we add the profileConnections to the clientConnections array
            $clientConnections[] = ['id' => $profileId, 'connections' => $profileConnections];
        }

        return $clientConnections;
    }

    /**
     * Disconnect all clients with this CN from all profiles and instances
     * managed by this service.
     *
     * @param string $commonName the CN to kill
     */
    public function kill($commonName)
    {
        $clientsKilled = 0;
        foreach (array_keys($this->portMapping) as $profileId) {
            foreach ($this->portMapping[$profileId]['managementPortList'] as $managementPort) {
                try {
                    // open the socket connection
                    $this->managementSocket->open(
                        sprintf('tcp://127.0.0.1:%d', $managementPort)
                    );

                    $response = $this->managementSocket->command(sprintf('kill %s', $commonName));
                    if (0 === strpos($response[0], 'SUCCESS: ')) {
                        ++$clientsKilled;
                    }
                    // close the socket connection
                    $this->managementSocket->close();
                } catch (ManagementSocketException $e) {
                    // we log the error, but continue with the next instance
                    $this->logger->error(
                        sprintf(
                            'tcp://127.0.0.1:%d: %s',
                            $managementPort,
                            $e->getMessage()
                        )
                    );
                }
            }
        }

        return 0 !== $clientsKilled;
    }
}
