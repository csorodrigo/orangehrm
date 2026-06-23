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

namespace CiaFerias\Help\Controller;

use Exception;
use CiaFerias\Authentication\Controller\ForbiddenController;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Framework\Http\RedirectResponse;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Help\Service\HelpService;

class HelpController extends AbstractVueController
{
    protected ?HelpService $helpService = null;

    public function getHelpService(): HelpService
    {
        return $this->helpService ??= new HelpService();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws RequestForwardableException
     */
    public function handle(Request $request)
    {
        if ($this->getHelpService()->isValidUrl()) {
            try {
                $label = $request->query->get('label');
                $redirectUrl = $this->getHelpService()->getRedirectUrl($label);
                return new RedirectResponse($redirectUrl);
            } catch (Exception $e) {
                $defaultRedirectUrl = $this->getHelpService()->getDefaultRedirectUrl();
                return new RedirectResponse($defaultRedirectUrl);
            }
        } else {
            throw new RequestForwardableException(ForbiddenController::class . '::handle');
        }
    }
}
