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

use SURFnet\VPN\Common\HttpClient\ServerClient;

class Connection
{
    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(ServerClient $serverClient)
    {
        $this->serverClient = $serverClient;
    }

    public function connect(array $envData)
    {
        $this->serverClient->postConnect(
            [
                'profile_id' => $envData['PROFILE_ID'],
                'common_name' => $envData['common_name'],
                'ip4' => $envData['ifconfig_pool_remote_ip'],
                'ip6' => $envData['ifconfig_pool_remote_ip6'],
                'connected_at' => $envData['time_unix'],
            ]
        );
    }

    public function disconnect(array $envData)
    {
        $this->serverClient->postDisconnect(
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
