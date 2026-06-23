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

use Composer\Autoload\ClassLoader;
use CiaFerias\Config\Config;
use CiaFerias\Core\Traits\EventDispatcherTrait;
use CiaFerias\Framework\Console\Console;
use CiaFerias\Framework\Console\ConsoleConfigurationInterface;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\PluginConfigurationInterface;
use CiaFerias\FunctionalTesting\Command\CreateDatabaseSavepointCommand;
use CiaFerias\FunctionalTesting\Command\DeleteDatabaseSavepointCommand;
use CiaFerias\FunctionalTesting\Command\ResetDatabaseCommand;
use CiaFerias\FunctionalTesting\Command\RestoreDatabaseToSavepointCommand;
use CiaFerias\FunctionalTesting\Subscriber\FunctionalTestingPluginSubscriber;

class FunctionalTestingPluginConfiguration implements PluginConfigurationInterface, ConsoleConfigurationInterface
{
    use EventDispatcherTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $loader = new ClassLoader();
        $loader->addPsr4('CiaFerias\\FunctionalTesting\\', [realpath(__DIR__ . '/..')]);
        $loader->register();

        $this->getEventDispatcher()->addSubscriber(new FunctionalTestingPluginSubscriber());
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Console $console): void
    {
        if (Config::PRODUCT_MODE !== Config::MODE_PROD) {
            $console->add(new CreateDatabaseSavepointCommand());
            $console->add(new RestoreDatabaseToSavepointCommand());
            $console->add(new DeleteDatabaseSavepointCommand());
            $console->add(new ResetDatabaseCommand());
        }
    }
}
