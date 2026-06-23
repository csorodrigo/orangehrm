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

namespace CiaFerias\Leave\Event;

final class LeaveEvent
{
    /**
     * @see \CiaFerias\Leave\Event\LeaveApply
     */
    public const APPLY = 'leave.apply';

    /**
     * @see \CiaFerias\Leave\Event\LeaveAssign
     */
    public const ASSIGN = 'leave.assign';

    /**
     * @see \CiaFerias\Leave\Event\LeaveApprove
     */
    public const APPROVE = 'leave.approve';

    /**
     * @see \CiaFerias\Leave\Event\LeaveCancel
     */
    public const CANCEL = 'leave.cancel';

    /**
     * @see \CiaFerias\Leave\Event\LeaveReject
     */
    public const REJECT = 'leave.reject';
}
