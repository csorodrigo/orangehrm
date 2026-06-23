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

namespace CiaFerias\Time\Controller;

use CiaFerias\Core\Authorization\Service\HomePageService;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Framework\Http\Request;

class TimesheetPeriodConfigController extends AbstractVueController
{
    use ConfigServiceTrait;
    use UserRoleManagerTrait;

    /**
     * @var HomePageService|null
     */
    protected ?HomePageService $homePageService = null;

    /**
     * @return HomePageService
     */
    public function getHomePageService(): HomePageService
    {
        if (!$this->homePageService instanceof HomePageService) {
            $this->homePageService = new HomePageService();
        }
        return $this->homePageService;
    }

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        // to block defineTimesheetPeriod (URL)
        $status = $this->getConfigService()->isTimesheetPeriodDefined();
        if (!$status) {
            if ($this->getUserRoleManager()->getDataGroupPermissions('attendance_configuration')->canUpdate()) {
                // config page of define start week
                $component = new Component('time-sheet-period');
            } else {
                // normal user -> warning page
                $component = new Component('time-sheet-period-not-defined');
            }
            $this->setComponent($component);
        } else {
            $defaultPath = $this->getHomePageService()->getTimeModuleDefaultPath();
            $this->setResponse($this->redirect($defaultPath));
        }
    }
}
