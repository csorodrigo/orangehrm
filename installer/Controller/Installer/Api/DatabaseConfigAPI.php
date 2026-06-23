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
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Installer\Controller\AbstractInstallerRestController;
use CiaFerias\Installer\Util\AppSetupUtility;
use CiaFerias\Installer\Util\StateContainer;

class DatabaseConfigAPI extends AbstractInstallerRestController
{
    /**
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        $dbType = $request->request->get('dbType');
        $dbHost = $request->request->get('dbHost');
        $dbPort = $request->request->get('dbPort');
        $dbUser = $request->request->get('dbUser');
        $dbPassword = $request->request->get('dbPassword');
        $dbName = $request->request->get('dbName');
        $enableDataEncryption = $request->request->getBoolean('enableDataEncryption');

        if ($dbType === AppSetupUtility::INSTALLATION_DB_TYPE_EXISTING &&
            ($request->request->has('useSameDbUserForCiaFerias') ||
                $request->request->has('ciaFeriasDbUser') ||
                $request->request->has('ciaFeriasDbPassword'))) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Unexpected Parameter `useSameDbUserForCiaFerias` or `ciaFeriasDbUser` or `ciaFeriasDbPassword` Received'
                ]
            ];
        }

        $appSetupUtility = new AppSetupUtility();
        if ($dbType === AppSetupUtility::INSTALLATION_DB_TYPE_NEW) {
            $useSameDbUserForCiaFerias = $request->request->getBoolean('useSameDbUserForCiaFerias', false);
            $ciaFeriasDbUser = $dbUser;
            $ciaFeriasDbPassword = $dbPassword;
            if (!$useSameDbUserForCiaFerias) {
                $ciaFeriasDbUser = $request->request->get('ciaFeriasDbUser');
                $ciaFeriasDbPassword = $request->request->get('ciaFeriasDbPassword');
            }

            StateContainer::getInstance()->storeDbInfo(
                $dbHost,
                $dbPort,
                new UserCredential($dbUser, $dbPassword),
                $dbName,
                new UserCredential($ciaFeriasDbUser, $ciaFeriasDbPassword),
                $enableDataEncryption
            );
            StateContainer::getInstance()->setDbType(AppSetupUtility::INSTALLATION_DB_TYPE_NEW);

            $connection = $appSetupUtility->connectToDatabaseServer();
            if ($connection->hasError()) {
                $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
                return [
                    'error' => [
                        'status' => $this->getResponse()->getStatusCode(),
                        'message' => $connection->getErrorMessage(),
                    ]
                ];
            } elseif ($appSetupUtility->isDatabaseExist($dbName)) {
                $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
                return [
                    'error' => [
                        'status' => $this->getResponse()->getStatusCode(),
                        'message' => 'Database Already Exist'
                    ]
                ];
            } elseif (!$useSameDbUserForCiaFerias && $appSetupUtility->isDatabaseUserExist($ciaFeriasDbUser)) {
                $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
                return [
                    'error' => [
                        'status' => $this->getResponse()->getStatusCode(),
                        'message' => "Database User `$ciaFeriasDbUser` Already Exist. Please Use Another Username for `CIA Férias Database Username`."
                    ]
                ];
            } else {
                $dbInfo = StateContainer::getInstance()->getDbInfo();
                return [
                    'data' => [
                        'dbHost' => $dbHost,
                        'dbPort' => $dbPort,
                        'dbUser' => $dbUser,
                        'dbName' => $dbName,
                        'useSameDbUserForCiaFerias' => $useSameDbUserForCiaFerias,
                        'ciaFeriasDbUser' => $useSameDbUserForCiaFerias ? null : ($dbInfo[StateContainer::CIA_FERIAS_DB_USER] ?? null),
                        'enableDataEncryption' => $enableDataEncryption,
                    ],
                    'meta' => []
                ];
            }
        }

        // `existing` database
        StateContainer::getInstance()->storeDbInfo(
            $dbHost,
            $dbPort,
            new UserCredential($dbUser, $dbPassword),
            $dbName,
            null,
            $enableDataEncryption
        );
        StateContainer::getInstance()->setDbType(AppSetupUtility::INSTALLATION_DB_TYPE_EXISTING);

        $connection = $appSetupUtility->connectToDatabase();
        if ($connection->hasError()) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => $connection->getErrorMessage(),
                ]
            ];
        } elseif (!$appSetupUtility->isExistingDatabaseEmpty()) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => 'Provided Database Not Empty'
                ]
            ];
        } else {
            return [
                'data' => [
                    'dbHost' => $dbHost,
                    'dbPort' => $dbPort,
                    'dbUser' => $dbUser,
                    'dbName' => $dbName,
                ],
                'meta' => []
            ];
        }
    }

    /**
     * @inheritDoc
     */
    protected function handleGet(Request $request): array
    {
        $dbInfo = StateContainer::getInstance()->getDbInfo();
        $useSameDbUserForCiaFerias = isset($dbInfo[StateContainer::CIA_FERIAS_DB_USER]) && $dbInfo[StateContainer::DB_USER] == $dbInfo[StateContainer::CIA_FERIAS_DB_USER];
        return [
            'data' => [
                'dbHost' => $dbInfo[StateContainer::DB_HOST],
                'dbPort' => $dbInfo[StateContainer::DB_PORT],
                'dbName' => $dbInfo[StateContainer::DB_NAME],
                'dbUser' => $dbInfo[StateContainer::DB_USER],
                'dbType' => StateContainer::getInstance()->getDbType(),
                'useSameDbUserForCiaFerias' => $useSameDbUserForCiaFerias,
                'ciaFeriasDbUser' => $useSameDbUserForCiaFerias ? null : ($dbInfo[StateContainer::CIA_FERIAS_DB_USER] ?? null),
                'enableDataEncryption' => $dbInfo[StateContainer::ENABLE_DATA_ENCRYPTION],
            ],
            'meta' => []
        ];
    }
}
