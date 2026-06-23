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

namespace CiaFerias\Core\Traits;

use CiaFerias\Core\Authorization\Helper\UserRoleManagerHelper;
use CiaFerias\Core\Authorization\Manager\AbstractUserRoleManager;
use CiaFerias\Core\Authorization\Manager\BasicUserRoleManager;
use CiaFerias\Framework\Services;

trait UserRoleManagerTrait
{
    use ServiceContainerTrait;

    /**
     * @return AbstractUserRoleManager|BasicUserRoleManager
     */
    protected function getUserRoleManager(): AbstractUserRoleManager
    {
        return $this->getContainer()->get(Services::USER_ROLE_MANAGER);
    }

    /**
     * @return UserRoleManagerHelper
     */
    protected function getUserRoleManagerHelper(): UserRoleManagerHelper
    {
        return $this->getContainer()->get(Services::USER_ROLE_MANAGER_HELPER);
    }
}
