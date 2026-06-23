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

namespace CiaFerias\Tests\Maintenance\AccessStrategy\FormatValue;

use CiaFerias\Config\Config;
use CiaFerias\Maintenance\AccessStrategy\FormatValue\FormatWithEducation;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

class FormatWithEducationTest extends KernelTestCase
{
    private string $fixture;
    private FormatWithEducation $formatWithEducation;

    protected function setUp(): void
    {
        $this->fixture = Config::get(Config::PLUGINS_DIR) . '/ciaFeriasMaintenancePlugin/test/fixtures/EmployeeDao.yml';
        TestDataService::populate($this->fixture);
        $this->formatWithEducation = new FormatWithEducation();
    }

    public function testGetFormattedValue(): void
    {
        $this->assertEquals('PhD', $this->formatWithEducation->getFormattedValue(1));
        $this->assertEquals(null, $this->formatWithEducation->getFormattedValue(5));
    }
}
