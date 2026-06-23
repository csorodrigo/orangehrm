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

namespace CiaFerias\Pim\Controller;

use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Helper\VueControllerHelper;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\WorkflowStateMachine;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class EmployeeController extends AbstractVueController
{
    use UserRoleManagerTrait;
    use EmployeeServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('employee-list');
        $component->addProp(
            new Prop(
                'unselectable-emp-numbers',
                Prop::TYPE_ARRAY,
                $this->getEmployeeService()->getUndeletableEmpNumbers()
            )
        );
        $this->setComponent($component);

        $allowedToDeleteActive = $this->getUserRoleManager()->isActionAllowed(
            WorkflowStateMachine::FLOW_EMPLOYEE,
            Employee::STATE_ACTIVE,
            WorkflowStateMachine::EMPLOYEE_ACTION_DELETE_ACTIVE
        );
        $allowedToDeleteTerminated = $this->getUserRoleManager()->isActionAllowed(
            WorkflowStateMachine::FLOW_EMPLOYEE,
            Employee::STATE_TERMINATED,
            WorkflowStateMachine::EMPLOYEE_ACTION_DELETE_TERMINATED
        );
        $permissionsArray['employee_list'] = [
            'canRead' => true,
            'canCreate' => $this->getUserRoleManager()->isActionAllowed(
                WorkflowStateMachine::FLOW_EMPLOYEE,
                Employee::STATE_NOT_EXIST,
                WorkflowStateMachine::EMPLOYEE_ACTION_ADD
            ),
            'canUpdate' => true,
            'canDelete' => $allowedToDeleteActive || $allowedToDeleteTerminated,
        ];
        $this->getContext()->set(
            VueControllerHelper::PERMISSIONS,
            $permissionsArray
        );
    }
}
