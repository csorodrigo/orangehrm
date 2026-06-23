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

namespace CiaFerias\Attendance\Controller;

use CiaFerias\Attendance\Traits\Service\AttendanceServiceTrait;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Entity\AttendanceRecord;
use CiaFerias\Core\Vue\Prop;

class PunchOutController extends AbstractVueController
{
    use AttendanceServiceTrait;
    use AuthUserTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        // check if previous record is a punch in.
        $attendanceRecord = $this->getAttendanceService()
            ->getAttendanceDao()
            ->getLastPunchRecordByEmployeeNumberAndActionableList(
                $this->getAuthUser()->getEmpNumber(),
                [AttendanceRecord::STATE_PUNCHED_IN]
            );

        //previous record is not present redirect to punch in
        if (!$attendanceRecord instanceof AttendanceRecord) {
            $this->setResponse($this->redirect('/attendance/punchIn'));
            return;
        }

        $component = new Component('attendance-punch-out');
        $component->addProp(new Prop('attendance-record-id', Prop::TYPE_NUMBER, $attendanceRecord->getId()));

        //if configuration enabled, editable is true
        if ($this->getAttendanceService()->canUserChangeCurrentTime()) {
            $component->addProp(new Prop('is-editable', Prop::TYPE_BOOLEAN, true));
        }
        $this->setComponent($component);
    }
}
