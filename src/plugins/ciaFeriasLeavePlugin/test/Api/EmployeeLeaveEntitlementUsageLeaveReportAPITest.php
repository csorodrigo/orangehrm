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

namespace CiaFerias\Tests\Leave\Api;

use CiaFerias\Config\Config;
use CiaFerias\Core\Traits\ORM\EntityManagerHelperTrait;
use CiaFerias\Framework\Services;
use CiaFerias\Leave\Api\LeaveReportAPI;
use CiaFerias\Tests\Util\EndpointIntegrationTestCase;
use CiaFerias\Tests\Util\Integration\TestCaseParams;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Leave
 * @group APIv2
 */
class EmployeeLeaveEntitlementUsageLeaveReportAPITest extends EndpointIntegrationTestCase
{
    use EntityManagerHelperTrait;

    public static function setUpBeforeClass(): void
    {
        TestDataService::populate(Config::get(Config::TEST_DIR) . '/phpunit/fixtures/DataGroupPermission.yaml', true);
        TestDataService::populate(
            Config::get(Config::PLUGINS_DIR) .
            '/ciaFeriasLeavePlugin/test/fixtures/EmployeeLeaveEntitlementUsageLeaveReportDataAPITest.yaml',
            true
        );
    }

    /**
     * @dataProvider dataProviderForTestGetAll
     */
    public function testGetAll(TestCaseParams $testCaseParams): void
    {
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $api = $this->getApiEndpointMock(LeaveReportAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getOne', $testCaseParams);
    }

    public function dataProviderForTestGetAll(): array
    {
        return $this->getTestCases('EmployeeLeaveEntitlementUsageLeaveReportAPITestCases.yaml', 'GetOne');
    }


    /**
     * @dataProvider dataProviderForTestGetAllWithLeavePeriodNotDefined
     */
    public function testGetAllWithLeavePeriodNotDefined(TestCaseParams $testCaseParams): void
    {
        $leavePeriodConfig = $this->getRepository(\CiaFerias\Entity\Config::class)->findOneBy(['name' => 'leave_period_defined']);
        $this->remove($leavePeriodConfig);

        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $api = $this->getApiEndpointMock(LeaveReportAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getOne', $testCaseParams);
    }

    public function dataProviderForTestGetAllWithLeavePeriodNotDefined(): array
    {
        return $this->getTestCases('EmployeeLeaveEntitlementUsageLeaveReportAPITestCases.yaml', 'GetOneWithNoLeavePeriod');
    }

    public function testDelete(): void
    {
        $api = new LeaveReportAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->delete();
    }

    public function testGetValidationRuleForDelete(): void
    {
        $api = new LeaveReportAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForDelete();
    }

    public function testUpdate(): void
    {
        $api = new LeaveReportAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->update();
    }

    public function testGetValidationRuleForUpdate(): void
    {
        $api = new LeaveReportAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForUpdate();
    }
}
