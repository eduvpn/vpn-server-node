<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SURFnet\VPN\Node;

use SURFnet\VPN\Common\FileIO;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Common\RandomInterface;

class Connection
{
    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    /** @var \SURFnet\VPN\Common\RandomInterface */
    private $random;

    public function __construct(ServerClient $serverClient, RandomInterface $random)
    {
        $this->serverClient = $serverClient;
        $this->random = $random;
    }

    public function connect(array $envData, $tmpFile)
    {
        $this->serverClient->post(
            'connect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
            ]
        );

        $uniqueTokenValue = $this->random->get(16);
        FileIO::writeFile($tmpFile, sprintf('push "auth-token %s"', $uniqueTokenValue));
    }

    public function disconnect(array $envData)
    {
        $this->serverClient->post(
            'disconnect',
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
                'disconnected_at' => $envData['time_unix'] + $envData['time_duration'],
                'bytes_transferred' => $envData['bytes_received'] + $envData['bytes_sent'],
            ]
        );
    }
}
