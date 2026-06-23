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

namespace CiaFerias\Tests\Admin\Service;

use CiaFerias\Admin\Dao\LicenseDao;
use CiaFerias\Admin\Dto\LicenseSearchFilterParams;
use CiaFerias\Admin\Service\LicenseService;
use CiaFerias\Config\Config;
use CiaFerias\Entity\License;
use CiaFerias\Tests\Util\TestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Admin
 * @group Service
 */
class LicenseServiceTest extends TestCase
{
    private LicenseService $licenseService;
    private string $fixture;

    public function testGetLicenseList(): void
    {
        $licenseList = TestDataService::loadObjectList('License', $this->fixture, 'License');
        $licenseFilterParams = new LicenseSearchFilterParams();
        $licenseDao = $this->getMockBuilder(LicenseDao::class)->getMock();
        $licenseDao->expects($this->once())
            ->method('getLicenseList')
            ->with($licenseFilterParams)
            ->will($this->returnValue($licenseList));
        $this->licenseService->setLicenseDao($licenseDao);
        $result = $this->licenseService->getLicenseList($licenseFilterParams);
        $this->assertCount(3, $result);
        $this->assertTrue($result[0] instanceof License);
    }

    public function testDeleteLicenses(): void
    {
        $toBeDeletedLicenseIds = [1, 2];
        $licenseDao = $this->getMockBuilder(LicenseDao::class)->getMock();
        $licenseDao->expects($this->once())
            ->method('deleteLicenses')
            ->with($toBeDeletedLicenseIds)
            ->will($this->returnValue(2));
        $this->licenseService->setLicenseDao($licenseDao);
        $result = $this->licenseService->deleteLicenses($toBeDeletedLicenseIds);
        $this->assertEquals(2, $result);
    }

    public function testGetLicenseById(): void
    {
        $licenseList = TestDataService::loadObjectList('License', $this->fixture, 'License');
        $licenseDao = $this->getMockBuilder(LicenseDao::class)->getMock();
        $licenseDao->expects($this->once())
            ->method('getLicenseById')
            ->with(1)
            ->will($this->returnValue($licenseList[0]));
        $this->licenseService->setLicenseDao($licenseDao);
        $result = $this->licenseService->getLicenseById(1);
        $this->assertEquals($licenseList[0], $result);
    }

    public function testGetLicenseByName(): void
    {
        $licenseList = TestDataService::loadObjectList('License', $this->fixture, 'License');
        $licenseDao = $this->getMockBuilder(LicenseDao::class)->getMock();
        $licenseDao->expects($this->once())
            ->method('getLicenseByName')
            ->with(1)
            ->will($this->returnValue($licenseList[0]));
        $this->licenseService->setLicenseDao($licenseDao);
        $result = $this->licenseService->getLicenseByName(1);
        $this->assertEquals($result, $licenseList[0]);
    }

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->licenseService = new LicenseService();
        $this->fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasAdminPlugin/test/fixtures/LicenseDao.yml';
        TestDataService::populate($this->fixture);
    }
}
