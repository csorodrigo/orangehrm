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

namespace CiaFerias\Tests\Dashboard\Service;

use DateTime;
use DateTimeZone;
use Exception;
use CiaFerias\Config\Config;
use CiaFerias\Core\Service\ConfigService;
use CiaFerias\Dashboard\Service\EmployeeTimeAtWorkService;
use CiaFerias\Framework\Services;
use CiaFerias\Pim\Service\EmployeeService;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Dashboard
 * @group Service
 */
class EmployeeTimeAtWorkServiceTest extends KernelTestCase
{
    protected string $fixture;
    protected EmployeeTimeAtWorkService $employeeTimeAtWorkService;

    /**
     * Set up method
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->employeeTimeAtWorkService = new EmployeeTimeAtWorkService();
        $this->fixture = Config::get(
            Config::PLUGINS_DIR
        ) . '/ciaFeriasDashboardPlugin/test/fixtures/EmployeeTimeAtWork.yaml';
        TestDataService::populate($this->fixture);
        $this->createKernelWithMockServices([
            Services::CONFIG_SERVICE => new ConfigService(),
            Services::EMPLOYEE_SERVICE => new EmployeeService()
        ]);
    }

    public function testGetTimeAtWorkResult()
    {
        $currentDateTime = new DateTime('2022-09-04 00:00:00', new DateTimeZone('Asia/Colombo'));
        $spotDateTime = new DateTime('2022-09-04 11:00:00', new DateTimeZone('Asia/Colombo'));
        $currentWeekData = $this->employeeTimeAtWorkService->getTimeAtWorkResults(4, $currentDateTime, $spotDateTime);
        $this->assertCount(2, $currentWeekData);

        $currentDateTime = new DateTime('2023-04-27 01:00:00', new DateTimeZone('Asia/Colombo'));
        $spotDateTime = new DateTime('2023-04-27 11:00:00', new DateTimeZone('Asia/Colombo'));
        $currentWeekData = $this->employeeTimeAtWorkService->getTimeAtWorkResults(9, $currentDateTime, $spotDateTime);
        $this->assertEquals(16, $currentWeekData[1]['currentWeek']['totalTime']['hours']);
        $this->assertEquals(0, $currentWeekData[1]['currentWeek']['totalTime']['minutes']);
        $this->assertCount(2, $currentWeekData);

        $currentDateTime = new DateTime('2023-05-04 22:30:00', new DateTimeZone('Asia/Colombo'));
        $spotDateTime = new DateTime('2023-05-04 22:30:00', new DateTimeZone('Asia/Colombo'));
        $currentWeekData = $this->employeeTimeAtWorkService->getTimeAtWorkResults(9, $currentDateTime, $spotDateTime);
        $this->assertEquals(76, $currentWeekData[1]['currentWeek']['totalTime']['hours']);
        $this->assertEquals(30, $currentWeekData[1]['currentWeek']['totalTime']['minutes']);
    }
}
