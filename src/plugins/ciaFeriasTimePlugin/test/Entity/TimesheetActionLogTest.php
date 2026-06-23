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

namespace CiaFerias\Tests\Time\Entity;

use DateTime;
use CiaFerias\Config\Config;
use CiaFerias\Entity\Timesheet;
use CiaFerias\Entity\TimesheetActionLog;
use CiaFerias\Entity\User;
use CiaFerias\Tests\Util\EntityTestCase;
use CiaFerias\Tests\Util\TestDataService;

class TimesheetActionLogTest extends EntityTestCase
{
    protected function setUp(): void
    {
        TestDataService::truncateSpecificTables([TimesheetActionLog::class]);
        $fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasTimePlugin/test/fixtures/TimesheetActionLogTest.yaml';
        TestDataService::populate($fixture);
        $this->getEntityManager()->clear();
    }

    public function testTimesheetActionLogEntity(): void
    {
        $timesheetActionLog = new TimesheetActionLog();
        $timesheetActionLog->setId(1);
        $timesheetActionLog->setDate(new DateTime('2021-12-06'));
        $timesheetActionLog->setAction('SUBMITTED');
        $timesheetActionLog->setTimesheet($this->getEntityReference(Timesheet::class, 1));
        $timesheetActionLog->setPerformedUser($this->getEntityReference(User::class, 1));
        $timesheetActionLog->setComment('Test comment');
        $this->persist($timesheetActionLog);

        $this->assertEquals(1, $timesheetActionLog->getId());
        $this->assertEquals('2021-12-06', $timesheetActionLog->getTimesheet()->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2021-12-12', $timesheetActionLog->getTimesheet()->getEndDate()->format('Y-m-d'));
        $this->assertEquals('2021-12-06', $timesheetActionLog->getDate()->format('Y-m-d'));
        $this->assertEquals(2, $timesheetActionLog->getPerformedUser()->getEmpNumber());
        $this->assertEquals('admin', $timesheetActionLog->getPerformedUser()->getUserName());
        $this->assertEquals('Test comment', $timesheetActionLog->getComment());
        $this->assertEquals('SUBMITTED', $timesheetActionLog->getAction());
    }
}
