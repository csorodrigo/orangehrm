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

use CiaFerias\Admin\Service\CompanyStructureService;
use CiaFerias\Config\Config;
use CiaFerias\Dashboard\Dao\ChartDao;
use CiaFerias\Dashboard\Service\ChartService;
use CiaFerias\Framework\Services;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Dashboard
 * @group Service
 */
class ChartServiceTest extends KernelTestCase
{
    protected string $fixture;
    private ChartService $chartService;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->chartService = new ChartService();
        $this->fixture = Config::get(
            Config::PLUGINS_DIR
        ) . '/ciaFeriasDashboardPlugin/test/fixtures/ChartDao.yml';
        TestDataService::populate($this->fixture);
        $this->createKernelWithMockServices([
            Services::COMPANY_STRUCTURE_SERVICE => new CompanyStructureService(),
        ]);
    }

    public function testGetChartDao(): void
    {
        $this->assertTrue($this->chartService->getChartDao() instanceof ChartDao);
    }

    public function testGetEmployeeDistributionBySubunit(): void
    {
        $distributionBySubunit = $this->chartService->getEmployeeDistributionBySubunit();
        $subunitCountPairs = $distributionBySubunit->getSubunitCountPairs();
        $this->assertEquals('0', $distributionBySubunit->getOtherEmployeeCount());
        $this->assertEquals('9', $distributionBySubunit->getTotalSubunitCount());
        $this->assertEquals('3', $distributionBySubunit->getUnassignedEmployeeCount());
        $this->assertEquals('8', $distributionBySubunit->getLimit());
        $this->assertEquals($distributionBySubunit->getTotalSubunitCount(), sizeof($subunitCountPairs));
    }
}
