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

namespace CiaFerias\Maintenance\Controller;

use CiaFerias\Authentication\Controller\AdminPrivilegeController;
use CiaFerias\Authentication\Controller\Traits\AdministratorAccessTrait;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Framework\Http\Request;

class AccessEmployeeController extends AbstractVueController implements AdminPrivilegeController
{
    use AuthUserTrait;
    use AdministratorAccessTrait;
    use ConfigServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('access-employee-search');

        $component->addProp(
            new Prop('instance-identifier', Prop::TYPE_STRING, $this->getConfigService()->getInstanceIdentifier())
        );

        $this->setComponent($component);
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request)
    {
        if (!$this->getAuthUser()->getHasAdminAccess()) {
            return $this->forwardToAdministratorAccess($request);
        }
        return parent::handle($request);
    }
}
