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
use CiaFerias\Entity\I18NLangString;
use CiaFerias\Entity\I18NLanguage;
use CiaFerias\Entity\I18NTranslation;
use CiaFerias\Framework\Services;
use CiaFerias\Leave\Api\LeaveReportAPI;
use CiaFerias\Tests\Util\EndpointIntegrationTestCase;
use CiaFerias\Tests\Util\Integration\TestCaseParams;
use CiaFerias\Tests\Util\TestDataService;

class LeaveTypeLeaveEntitlementUsageReportAPITest extends EndpointIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        TestDataService::truncateSpecificTables([I18NTranslation::class, I18NLangString::class, I18NLanguage::class]);
        TestDataService::populate(Config::get(Config::TEST_DIR) . '/phpunit/fixtures/DataGroupPermission.yaml', true);
        TestDataService::populate(
            Config::get(Config::PLUGINS_DIR) .
            '/ciaFeriasLeavePlugin/test/fixtures/LeaveTypeLeaveEntitlementUsageLeaveReportDataAPITest.yaml',
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
        $this->registerServices($testCaseParams);
        $api = $this->getApiEndpointMock(LeaveReportAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getOne', $testCaseParams);
    }

    public function dataProviderForTestGetAll(): array
    {
        return $this->getTestCases('LeaveTypeLeaveEntitlementUsageLeaveReportAPITestCases.yaml', 'GetOne');
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
