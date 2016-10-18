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

use Psr\Log\LoggerInterface;
use SURFnet\VPN\Common\HttpClient\ServerClient;
use SURFnet\VPN\Node\Exception\InputValidationException;

class Connection
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \SURFnet\VPN\Common\HttpClient\ServerClient */
    private $serverClient;

    public function __construct(LoggerInterface $logger, ServerClient $serverClient)
    {
        $this->logger = $logger;
        $this->serverClient = $serverClient;
    }

    public function connect(array $envData)
    {
        try {
            $poolId = InputValidation::poolId($envData['POOL_ID']);
            $commonName = InputValidation::commonName($envData['common_name']);
            $userId = self::getUserId($commonName);

            // XXX turn the >= 3 calls below into one call

            // check if user is disabled
            if (true === $this->serverClient->isDisabledUser($userId)) {
                $this->logger->error('user is disabled', $envData);

                return false;
            }

            // check if the common_name is disabled
            if (true === $this->serverClient->isDisabledCommonName($commonName)) {
                $this->logger->error('common_name is disabled', $envData);

                return false;
            }

            // if the ACL is enabled, verify that the user is allowed to
            // connect
            $serverPool = $this->serverClient->serverPool($poolId);
            if ($serverPool['enableAcl']) {
                $userGroups = $this->serverClient->userGroups($userId);
                if (false === self::isMember($userGroups, $serverPool['aclGroupList'])) {
                    $this->logger->error('user is not a member of required group', $envData);

                    return false;
                }
            }

            $this->logger->info(json_encode(array_merge($envData, ['ok' => true])));

            return true;
        } catch (InputValidationException $e) {
            $this->logger->error($e->getMessage(), $envData);

            return false;
        }
    }

    public function disconnect(array $envData)
    {
        $this->logger->info(json_encode(array_merge($envData, ['ok' => true])));

        return true;
    }

    private static function isMember(array $memberOf, array $aclGroupList)
    {
        // one of the groups must be listed in the pool ACL list
        foreach ($memberOf as $memberGroup) {
            if (in_array($memberGroup['id'], $aclGroupList)) {
                return true;
            }
        }

        return false;
    }

    private static function getUserId($commonName)
    {
        // XXX share this with "Otp" class and possibly others

        // return the part before the first underscore, it is already validated
        // so we can be sure this is fine
        return substr($commonName, 0, strpos($commonName, '_'));
    }
}
