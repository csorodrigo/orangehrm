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
use CiaFerias\Core\Controller\Common\NoRecordsFoundController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\AttendanceRecord;
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\WorkflowStateMachine;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class ViewEmployeeAttendanceController extends AbstractVueController
{
    use AuthUserTrait;
    use UserRoleManagerTrait;
    use EmployeeServiceTrait;
    use AttendanceServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        if ($request->query->has('employeeId')) {
            $empNumber = $request->query->getInt('employeeId');
            if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($empNumber)) {
                throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
            }
            $component = new Component('view-employee-attendance-detailed');
            $component->addProp(
                new Prop(
                    'employee',
                    Prop::TYPE_OBJECT,
                    $this->getEmployeeService()->getEmployeeAsArray($empNumber)
                )
            );
            $loggedInUserEmpNumber = $this->getAuthUser()->getEmpNumber();
            $rolesToInclude = [];
            //check the configuration as ESS since Admin user is always allowed to delete self records
            if ($empNumber === $loggedInUserEmpNumber) {
                $rolesToInclude = ['ESS'];
            }
            //If edit/delete own attendance record, get the allowed actions list as an ESS user
            //since Admin is always allowed to edit/delete own record
            //If delete someone else's attendance record, get the allowed actions list as a Supervisor
            //Admin is always allowed to edit/delete others records
            $allowedWorkflowItems = $this->getUserRoleManager()->getAllowedActions(
                WorkflowStateMachine::FLOW_ATTENDANCE,
                AttendanceRecord::STATE_PUNCHED_IN,
                [],
                $rolesToInclude,
                [Employee::class => $empNumber]
            );
            if (in_array(WorkflowStateMachine::ATTENDANCE_ACTION_DELETE, array_keys($allowedWorkflowItems))) {
                $component->addProp(new Prop('is-editable', Prop::TYPE_BOOLEAN, true));
            }
        } else {
            $component = new Component('view-employee-attendance-summary');
        }

        if ($request->query->has('date')) {
            $component->addProp(new Prop('date', Prop::TYPE_STRING, $request->query->get('date')));
        }
        $this->setComponent($component);
    }
}
