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

namespace CiaFerias\Tests\Performance\Dao;

use CiaFerias\Config\Config;
use CiaFerias\Core\Service\DateTimeHelperService;
use CiaFerias\Entity\Kpi;
use CiaFerias\Framework\Services;
use CiaFerias\Performance\Dao\KpiDao;
use CiaFerias\Performance\Dto\KpiSearchFilterParams;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Performance
 * @group Dao
 */
class KpiDaoTest extends KernelTestCase
{
    private KpiDao $kpiDao;
    protected string $fixture;

    protected function setUp(): void
    {
        $this->kpiDao = new KpiDao();
        $this->fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasPerformancePlugin/test/fixtures/KpiDao.yaml';
        TestDataService::populate($this->fixture);
    }

    public function testSaveKpi(): void
    {
        $kpi = new Kpi();
        $kpi->getDecorator()->setJobTitleById(1);
        $kpi->setTitle('indicator 4');
        $kpi->setMinRating(0);
        $kpi->setMaxRating(100);
        $kpi->setDefaultKpi(true);
        $result = $this->kpiDao->saveKpi($kpi);
        $this->assertEquals($kpi, $result);
    }

    public function testGetKpiById(): void
    {
        $result = $this->kpiDao->getKpiById(3);
        $this->assertEquals('Planning Methodologies', $result->getTitle());
        $this->assertEquals('Quality Assurance', $result->getJobTitle()->getJobTitleName());
        $this->assertEquals(1, $result->getMinRating());
        $this->assertEquals(50, $result->getMaxRating());
        $this->assertTrue($result->isDefaultKpi());

        $result = $this->kpiDao->getKpiById(100);
        $this->assertNull($result);
    }

    public function testGetKpiList(): void
    {
        $kpiSearchFilterParams = new KpiSearchFilterParams();
        $result = $this->kpiDao->getKpiList($kpiSearchFilterParams);
        $this->assertCount(5, $result);
        $this->assertEquals('Capacity Planning', $result[0]->getTitle());
        $this->assertEquals('Code Clarity', $result[1]->getTitle());
        $this->assertEquals(10, $result[1]->getMaxRating());

        $kpiSearchFilterParams->setJobTitleId(1);
        $result = $this->kpiDao->getKpiList($kpiSearchFilterParams);
        $this->assertCount(1, $result);
    }

    public function testGetKpiCount(): void
    {
        $kpiSearchFilterParams = new KpiSearchFilterParams();
        $result = $this->kpiDao->getKpiCount($kpiSearchFilterParams);
        $this->assertEquals(5, $result);

        $kpiSearchFilterParams->setJobTitleId(100);
        $result = $this->kpiDao->getKpiCount($kpiSearchFilterParams);
        $this->assertEquals(0, $result);
    }

    public function testDeleteKpi(): void
    {
        $this->createKernelWithMockServices([Services::DATETIME_HELPER_SERVICE => new DateTimeHelperService()]);
        $toBeDeletedIds = [2, 3];
        $result = $this->kpiDao->deleteKpi($toBeDeletedIds);
        $this->assertEquals(2, $result);

        $toBeDeletedIds = [];
        $result = $this->kpiDao->deleteKpi($toBeDeletedIds);
        $this->assertEquals(0, $result);
    }

    public function testUnsetDefaultKpi(): void
    {
        $result = $this->getEntityManager()->getRepository(Kpi::class)->findBy(['defaultKpi' => true]);
        $this->assertCount(1, $result);

        $this->kpiDao->unsetDefaultKpi(null);

        $result = $this->getEntityManager()->getRepository(Kpi::class)->findBy(['defaultKpi' => true]);
        $this->assertEmpty($result);
    }

    public function testUnsetDefaultKpiWithId(): void
    {
        $result = $this->getEntityManager()->getRepository(Kpi::class)->findOneBy(['defaultKpi' => true]);
        $this->assertEquals('Planning Methodologies', $result->getTitle());

        $this->kpiDao->unsetDefaultKpi(3);

        $result = $this->getEntityManager()->getRepository(Kpi::class)->findOneBy(['defaultKpi' => true]);
        $this->assertEquals('Planning Methodologies', $result->getTitle());
    }

    public function testGetDefaultKpi(): void
    {
        $expected = $this->getEntityManager()->getRepository(Kpi::class)->findOneBy(['defaultKpi' => true]);
        $result = $this->kpiDao->getDefaultKpi();

        $this->assertEquals($expected, $result);
    }
}
