<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Node\OpenVpn;

/**
 * Parses the response from the OpenVPN `status 2` command.
 */
class StatusParser
{
    public static function parse(array $statusData)
    {
        $clientList = [];

        // find "HEADER,CLIENT_LIST"
        $i = 0;
        while (0 !== strpos($statusData[$i], 'HEADER,CLIENT_LIST')) {
            ++$i;
        }
        $clientKeys = array_slice(str_getcsv($statusData[$i]), 2);
        ++$i;
        // iterate over all CLIENT_LIST entries
        while (0 === strpos($statusData[$i], 'CLIENT_LIST')) {
            $clientValues = str_getcsv($statusData[$i]);
            array_shift($clientValues);
            $clientInfo = array_combine($clientKeys, $clientValues);
            $clientList[] = [
                'common_name' => $clientInfo['Common Name'],
                'virtual_address' => [
                    $clientInfo['Virtual Address'],
                    $clientInfo['Virtual IPv6 Address'],
                ],
            ];
            ++$i;
        }

        return $clientList;
    }
}
