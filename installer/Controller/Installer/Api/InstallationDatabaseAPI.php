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

use InvalidArgumentException;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Installer\Controller\AbstractInstallerRestController;
use CiaFerias\Installer\Util\AppSetupUtility;

class InstallationDatabaseAPI extends AbstractInstallerRestController
{
    /**
     * @inheritDoc
     */
    protected function handlePost(Request $request): array
    {
        $appSetupUtility = new AppSetupUtility();
        try {
            $appSetupUtility->createDatabase();
        } catch (InvalidArgumentException $e) {
            $this->getResponse()->setStatusCode(Response::HTTP_BAD_REQUEST);
            return [
                'error' => [
                    'status' => $this->getResponse()->getStatusCode(),
                    'message' => $e->getMessage()
                ]
            ];
        }
        return [];
    }
}
