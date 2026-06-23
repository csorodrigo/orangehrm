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

namespace CiaFerias\Tests\Claim\Dao;

use CiaFerias\Claim\Dao\ClaimDao;
use CiaFerias\Claim\Dto\ClaimExpenseTypeSearchFilterParams;
use CiaFerias\Config\Config;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Claim
 * @group Dao
 */
class ClaimDaoExpenseTypeTest extends KernelTestCase
{
    private ClaimDao $claimDao;

    protected function setUp(): void
    {
        $this->claimDao = new ClaimDao();
        $expenseTypeFixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasClaimPlugin/test/fixtures/ExpenseType.yaml';
        TestDataService::populate($expenseTypeFixture);
    }

    public function testGetExpenseTypeList(): void
    {
        $expenseTypeSearchFilterParams = new ClaimExpenseTypeSearchFilterParams();
        $expenseTypeSearchFilterParams->setName(null);
        $expenseTypeSearchFilterParams->setStatus(null);
        $expenseTypeSearchFilterParams->setId(null);
        $result = $this->claimDao->getExpenseTypeList($expenseTypeSearchFilterParams);
        $this->assertEquals("medical", $result[0]->getName());
        $this->assertCount("4", $result);
    }

    public function testGetExpenseTypeById(): void
    {
        $result = $this->claimDao->getExpenseTypeById(4);
        $this->assertEquals("stationary", $result->getName());
    }

    public function testDeleteExpenseType(): void
    {
        $result = $this->claimDao->deleteExpenseTypes([1, 2]);
        $this->assertEquals(2, $result);
    }

    public function testIsExpenseTypeUsed(): void
    {
        $result = $this->claimDao->isExpenseTypeUsed(1);
        $this->assertEquals(false, $result);
    }
}
