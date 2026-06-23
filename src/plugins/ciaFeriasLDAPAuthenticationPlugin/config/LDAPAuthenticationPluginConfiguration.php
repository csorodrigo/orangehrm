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

use CiaFerias\Authentication\Auth\AuthProviderChain;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Framework\Console\Console;
use CiaFerias\Framework\Console\ConsoleConfigurationInterface;
use CiaFerias\Framework\Console\Scheduling\CommandInfo;
use CiaFerias\Framework\Console\Scheduling\Schedule;
use CiaFerias\Framework\Console\Scheduling\SchedulerConfigurationInterface;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Logger\LoggerFactory;
use CiaFerias\Framework\PluginConfigurationInterface;
use CiaFerias\Framework\Services;
use CiaFerias\LDAP\Auth\LDAPAuthProvider;
use CiaFerias\LDAP\Command\LDAPSyncUserCommand;
use CiaFerias\LDAP\Dto\LDAPSetting;

class LDAPAuthenticationPluginConfiguration implements
    PluginConfigurationInterface,
    ConsoleConfigurationInterface,
    SchedulerConfigurationInterface
{
    use ServiceContainerTrait;
    use ConfigServiceTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $this->getContainer()->register(Services::LDAP_LOGGER)
            ->setFactory([LoggerFactory::class, 'getLogger'])
            ->addArgument('LDAP')
            ->addArgument('ldap.log');
        $ldapSettings = $this->getConfigService()->getLDAPSetting();
        if ($ldapSettings instanceof LDAPSetting && $ldapSettings->isEnable()) {
            /** @var AuthProviderChain $authProviderChain */
            $authProviderChain = $this->getContainer()->get(Services::AUTH_PROVIDER_CHAIN);
            $authProviderChain->addProvider(new LDAPAuthProvider());
        }
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Console $console): void
    {
        $console->add(new LDAPSyncUserCommand());
    }

    /**
     * @inheritDoc
     */
    public function schedule(Schedule $schedule): void
    {
        $ldapSettings = $this->getConfigService()->getLDAPSetting();
        if ($ldapSettings instanceof LDAPSetting && $ldapSettings->isEnable()) {
            $interval = 1;
            if ($ldapSettings->getSyncInterval() <= 23 && $ldapSettings->getSyncInterval() >= 1) {
                $interval = $ldapSettings->getSyncInterval();
            }

            $schedule->add(new CommandInfo('cia-ferias:ldap-sync-user'))
                ->cron("0 */$interval * * *");
        }
    }
}
