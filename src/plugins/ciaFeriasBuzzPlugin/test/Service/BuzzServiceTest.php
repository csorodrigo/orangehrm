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

namespace CiaFerias\Tests\Buzz\Service;

use CiaFerias\Buzz\Dao\BuzzDao;
use CiaFerias\Buzz\Dao\BuzzLikeDao;
use CiaFerias\Buzz\Service\BuzzService;
use CiaFerias\Tests\Util\TestCase;

/**
 * @group Buzz
 * @group Service
 */
class BuzzServiceTest extends TestCase
{
    private BuzzService $buzzService;

    protected function setUp(): void
    {
        $this->buzzService = new BuzzService();
    }

    public function testGetBuzzDao(): void
    {
        $this->assertTrue($this->buzzService->getBuzzDao() instanceof BuzzDao);
    }

    public function testGetBuzzLikeDao(): void
    {
        $this->assertTrue($this->buzzService->getBuzzLikeDao() instanceof BuzzLikeDao);
    }
}
