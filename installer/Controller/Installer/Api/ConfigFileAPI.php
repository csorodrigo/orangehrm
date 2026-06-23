<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace CiaFerias\Installer\Controller\Installer\Api;

use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Core\Exception\KeyHandlerException;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Installer\Util\AppSetupUtility;
use CiaFerias\Installer\Util\DataRegistrationUtility;
use CiaFerias\Installer\Util\StateContainer;

class ConfigFileAPI extends \CiaFerias\Installer\Controller\Upgrader\Api\ConfigFileAPI
{
    /**
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        if (StateContainer::getInstance()->isSetDbInfo()) {
            $dbInfo = StateContainer::getInstance()->getDbInfo();

            if ($dbInfo[StateContainer::ENABLE_DATA_ENCRYPTION]) {
                try {
                    $appSetupUtility = new AppSetupUtility();
                    $appSetupUtility->writeKeyFile();
                } catch (KeyHandlerException $exception) {
                    $this->getResponse()->setStatusCode(Response::HTTP_CONFLICT);
                    return
                        [
                            'error' => [
                                'status' => $this->getResponse()->getStatusCode(),
                                'message' => $exception->getMessage()
                            ]
                        ];
                }
            }

            $dbUser = $dbInfo[StateContainer::CIA_FERIAS_DB_USER] ?? $dbInfo[StateContainer::DB_USER];
            $dbPassword = isset($dbInfo[StateContainer::CIA_FERIAS_DB_USER])
                ? $dbInfo[StateContainer::CIA_FERIAS_DB_PASSWORD]
                : $dbInfo[StateContainer::DB_PASSWORD];
            StateContainer::getInstance()->storeDbInfo(
                $dbInfo[StateContainer::DB_HOST],
                $dbInfo[StateContainer::DB_PORT],
                new UserCredential($dbUser, $dbPassword),
                $dbInfo[StateContainer::DB_NAME]
            );
        }
        return parent::handlePost($request);
    }

    /**
     * @inheritDoc
     */
    protected function getRegistrationType(): int
    {
        return DataRegistrationUtility::REGISTRATION_TYPE_INSTALLER_STARTED;
    }
}
