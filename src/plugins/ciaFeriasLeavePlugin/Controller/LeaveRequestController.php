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

namespace CiaFerias\Leave\Controller;

use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\Common\NoRecordsFoundController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\LeaveRequest;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Leave\Traits\Service\LeaveRequestServiceTrait;

class LeaveRequestController extends AbstractVueController
{
    use LeaveRequestServiceTrait;
    use UserRoleManagerTrait;

    public function preRender(Request $request): void
    {
        if (!$request->attributes->has('id')) {
            throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
        }

        $id = $request->attributes->getInt('id');

        $leaveRequestRecord = $this->getLeaveRequestService()
            ->getLeaveRequestDao()
            ->getLeaveRequestById($id);
        if (!$leaveRequestRecord instanceof LeaveRequest ||
            !$this->getUserRoleManagerHelper()->isEmployeeAccessible(
                $leaveRequestRecord->getEmployee()->getEmpNumber()
            )) {
            throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
        }

        $component = new Component('leave-view-request');
        $component->addProp(new Prop('leave-request-id', Prop::TYPE_NUMBER, $id));
        if ($request->query->has('mode') && $request->query->get('mode') === "my-leave") {
            $component->addProp(new Prop('my-leave-request', Prop::TYPE_BOOLEAN, true));
        }
        $this->setComponent($component);
    }
}
