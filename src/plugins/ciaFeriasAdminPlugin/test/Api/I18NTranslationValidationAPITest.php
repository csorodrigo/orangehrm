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

namespace CiaFerias\Tests\Admin\Api;

use CiaFerias\Admin\Api\I18NTranslationValidationAPI;
use CiaFerias\Entity\I18NError;
use CiaFerias\Entity\I18NGroup;
use CiaFerias\Entity\I18NLangString;
use CiaFerias\Entity\I18NLanguage;
use CiaFerias\Framework\Services;
use CiaFerias\Tests\Util\EndpointIntegrationTestCase;
use CiaFerias\Tests\Util\Integration\TestCaseParams;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Admin
 * @group APIv2
 */
class I18NTranslationValidationAPITest extends EndpointIntegrationTestCase
{
    protected function setUp(): void
    {
        TestDataService::truncateSpecificTables([
            I18NError::class,
            I18NLangString::class,
            I18NLanguage::class,
            I18NGroup::class
        ]);
        $this->populateFixtures('I18NTranslationValidationAPITest.yml');
    }

    /**
     * @dataProvider dataProviderForTestGetOne
     */
    public function testGetOne(TestCaseParams $testCaseParams): void
    {
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerServices($testCaseParams);
        $api = $this->getApiEndpointMock(I18NTranslationValidationAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getOne', $testCaseParams);
    }

    public function dataProviderForTestGetOne(): array
    {
        return $this->getTestCases('I18NTranslationValidationAPITestCases.yml', 'GetOne');
    }

    public function testUpdate(): void
    {
        $api = new I18NTranslationValidationAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->update();
    }

    public function testGetValidationRuleForUpdate(): void
    {
        $api = new I18NTranslationValidationAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForUpdate();
    }

    public function testDelete(): void
    {
        $api = new I18NTranslationValidationAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->delete();
    }

    public function testGetValidationRuleForDelete(): void
    {
        $api = new I18NTranslationValidationAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForDelete();
    }

}
