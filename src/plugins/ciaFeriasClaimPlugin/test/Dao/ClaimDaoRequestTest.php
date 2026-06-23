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
use CiaFerias\Claim\Dto\ClaimRequestSearchFilterParams;
use CiaFerias\Config\Config;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Claim
 * @group Dao
 */
class ClaimDaoRequestTest extends KernelTestCase
{
    private ClaimDao $claimDao;

    protected function setUp(): void
    {
        $this->claimDao = new ClaimDao();
        $requestFixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasClaimPlugin/test/fixtures/MyClaimRequestAPITest.yaml';
        TestDataService::populate($requestFixture);
    }

    public function testGetClaimRequestById(): void
    {
        $result = $this->claimDao->getClaimRequestById(1);
        $this->assertEquals(1, $result->getId());
    }

    public function testGetClaimRequestList(): void
    {
        $claimRequestSearchFilterParams = new ClaimRequestSearchFilterParams();
        $claimRequestSearchFilterParams->setEmpNumbers([4]);
        $claimRequests = $this->claimDao->getClaimRequestList($claimRequestSearchFilterParams);
        $this->assertEquals(4, count($claimRequests));
    }
}
