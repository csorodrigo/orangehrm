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

namespace CiaFerias\Tests\Pim\Service;

use CiaFerias\Config\Config;
use CiaFerias\Core\Dao\ReportGeneratorDao;
use CiaFerias\Core\Service\ReportGeneratorService;
use CiaFerias\Tests\Util\TestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Pim
 * @group Service
 */
class PimDefinedReportServiceTest extends TestCase
{
    /**
     * @var ReportGeneratorService
     */
    private ReportGeneratorService $reportGeneratorService;

    /**
     * @var string
     */
    protected string $fixture;

    protected function setUp(): void
    {
        $this->reportGeneratorService = new ReportGeneratorService();
        $this->fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasPimPlugin/test/fixtures/ReportGeneratorService.yaml';
        TestDataService::populate($this->fixture);
    }

    public function testGetReportGeneratorDao(): void
    {
        $this->assertTrue($this->reportGeneratorService->getReportGeneratorDao() instanceof ReportGeneratorDao);
    }
}
