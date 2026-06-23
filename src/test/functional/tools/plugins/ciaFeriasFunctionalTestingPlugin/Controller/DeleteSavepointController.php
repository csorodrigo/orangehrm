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

namespace CiaFerias\FunctionalTesting\Controller;

use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;

class DeleteSavepointController extends AbstractController implements PublicControllerInterface
{
    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $savepointNames = $request->request->get('savepointNames', []);
        $response = $this->getResponse();
        $response->setContent(
            json_encode(['success' => $this->getDatabaseBackupService()->deleteSavepoints($savepointNames)])
        );
        return $response;
    }
}
