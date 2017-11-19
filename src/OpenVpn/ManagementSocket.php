<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\OpenVpn;

use SURFnet\VPN\Node\OpenVpn\Exception\ManagementSocketException;

/**
 * Abstraction to use the OpenVPN management interface using a socket
 * connection.
 */
class ManagementSocket implements ManagementSocketInterface
{
    /** @var resource|null */
    private $socket;

    public function __construct()
    {
        $this->socket = null;
    }

    /**
     * Connect to an OpenVPN management socket.
     *
     * @param string $socketAddress the socket to connect to, e.g.:
     *                              "tcp://localhost:7505"
     * @param int    $timeOut
     */
    public function open($socketAddress, $timeOut = 5)
    {
        $this->socket = @stream_socket_client($socketAddress, $errno, $errstr, $timeOut);
        if (false === $this->socket) {
            throw new ManagementSocketException(
                sprintf('%s (%s)', $errstr, $errno)
            );
        }
        // turn off logging as the output may interfere with our management
        // session, we do not care about the output
        $this->command('log off');
    }

    /**
     * Send an OpenVPN command and get the response.
     *
     * @param string $command a OpenVPN management command and parameters
     *
     * @throws Exception\ServerSocketException in case read/write fails or
     *                                         socket is not open
     *
     * @return array the response lines as array values
     */
    public function command($command)
    {
        $this->requireOpenSocket();
        $this->write(
            sprintf("%s\n", $command)
        );

        return $this->read();
    }

    public function close()
    {
        $this->requireOpenSocket();
        if (false === @fclose($this->socket)) {
            throw new ManagementSocketException('unable to close the socket');
        }
    }

    private function write($data)
    {
        if (false === @fwrite($this->socket, $data)) {
            throw new ManagementSocketException('unable to write to socket');
        }
    }

    private function read()
    {
        $dataBuffer = [];
        while (!feof($this->socket) && !$this->isEndOfResponse(end($dataBuffer))) {
            if (false === $readData = @fgets($this->socket, 4096)) {
                throw new ManagementSocketException('unable to read from socket');
            }
            $dataBuffer[] = trim($readData);
        }

        return $dataBuffer;
    }

    private function isEndOfResponse($lastLine)
    {
        $endMarkers = ['END', 'SUCCESS: ', 'ERROR: '];
        foreach ($endMarkers as $endMarker) {
            if (0 === strpos($lastLine, $endMarker)) {
                return true;
            }
        }

        return false;
    }

    private function requireOpenSocket()
    {
        if (null === $this->socket) {
            throw new ManagementSocketException('socket not open');
        }
    }
}
