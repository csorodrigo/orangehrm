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

use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\Service\DateTimeHelperTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\Timesheet;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Time\Traits\Service\TimesheetServiceTrait;

class MyTimesheetController extends AbstractVueController
{
    use AuthUserTrait;
    use DateTimeHelperTrait;
    use TimesheetServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $this->createDefaultTimesheetIfNotExist();
        $component = new Component('my-timesheet');
        if ($request->query->has('startDate')) {
            $component->addProp(new Prop('start-date', Prop::TYPE_STRING, $request->query->get('startDate')));
        }
        $this->setComponent($component);
    }

    /**
     * @return void
     */
    private function createDefaultTimesheetIfNotExist(): void
    {
        $currentDate = $this->getDateTimeHelper()->getNow();
        $status = $this->getTimesheetService()->hasTimesheetForDate($this->getAuthUser()->getEmpNumber(), $currentDate);
        if (!$status) {
            $timesheet = new Timesheet();
            $timesheet->getDecorator()->setEmployeeByEmployeeNumber($this->getAuthUser()->getEmpNumber());
            $this->getTimesheetService()->createTimesheetByDate($timesheet, $currentDate);
        }
    }
}
