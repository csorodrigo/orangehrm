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

namespace CiaFerias\Tests\Help\Service;

use CiaFerias\Config\Config;
use CiaFerias\Help\Service\HelpConfigService;
use CiaFerias\Tests\Util\KernelTestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Help
 * @group Service
 */
class HelpConfigServiceTest extends KernelTestCase
{
    private HelpConfigService $helpConfigService;
    protected string $fixture;

    protected function setUp(): void
    {
        $this->helpConfigService = new HelpConfigService();
        $this->fixture = Config::get(
            Config::PLUGINS_DIR
        ) . '/ciaFeriasHelpPlugin/test/fixtures/HelpServiceTest.yaml';
        TestDataService::populate($this->fixture);
    }

    public function testGetHelpProcessorClass(): void
    {
        $helpProcessorClass = $this->helpConfigService->getHelpProcessorClass();
        $this->assertEquals('ZendeskHelpProcessor', $helpProcessorClass);
    }

    public function testGetBaseHelpUrl(): void
    {
        $baseHelpUrl = $this->helpConfigService->getBaseHelpUrl();
        $this->assertEquals('', $baseHelpUrl);
    }
}
