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
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\Timesheet;
use CiaFerias\Tests\Util\EntityTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Time
 * @group Entity
 */
class TimesheetTest extends EntityTestCase
{
    protected function setUp(): void
    {
        TestDataService::truncateSpecificTables([Timesheet::class]);
        $fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasTimePlugin/test/fixtures/TimesheetTest.yaml';
        TestDataService::populate($fixture);
        $this->getEntityManager()->clear();
    }

    public function testTimesheetEntity(): void
    {
        $this->assertTrue(true);
        $timesheet = new Timesheet();
        $timesheet->setState('INITIAL');
        $timesheet->setId(1);
        $timesheet->setStartDate(new DateTime('2021-12-06'));
        $timesheet->setEndDate(new DateTime('2021-12-12'));
        $timesheet->setEmployee($this->getEntityReference(Employee::class, 1));
        $this->persist($timesheet);

        $this->assertEquals(1, $timesheet->getId());
        $this->assertEquals('INITIAL', $timesheet->getState());
        $this->assertEquals('2021-12-06', $timesheet->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2021-12-12', $timesheet->getEndDate()->format('Y-m-d'));
        $this->assertEquals(1, $timesheet->getEmployee()->getEmpNumber());
        $this->assertEquals('Kayla', $timesheet->getEmployee()->getFirstName());
        $this->assertEquals('Abbey', $timesheet->getEmployee()->getLastName());
    }
}
