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
use CiaFerias\Authentication\Auth\LocalAuthProvider;
use CiaFerias\Authentication\Auth\User as AuthUser;
use CiaFerias\Authentication\Csrf\CsrfTokenManager;
use CiaFerias\Authentication\Service\AuthenticationService;
use CiaFerias\Authentication\Service\PasswordStrengthService;
use CiaFerias\Authentication\Subscriber\AdministratorAccessSubscriber;
use CiaFerias\Authentication\Subscriber\AuthenticationSubscriber;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Framework\Event\EventDispatcher;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\PluginConfigurationInterface;
use CiaFerias\Framework\Services;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;

class AuthenticationPluginConfiguration implements PluginConfigurationInterface
{
    use ServiceContainerTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getContainer()->get(Services::EVENT_DISPATCHER);
        $dispatcher->addSubscriber(new AuthenticationSubscriber());
        $dispatcher->addSubscriber(new AdministratorAccessSubscriber());
        $this->getContainer()->register(Services::AUTH_USER)
            ->setFactory([AuthUser::class, 'getInstance']);

        $this->getContainer()->register(Services::CSRF_TOKEN_STORAGE, NativeSessionTokenStorage::class);
        $this->getContainer()->register(Services::CSRF_TOKEN_MANAGER, CsrfTokenManager::class);
        /** @var AuthProviderChain $authProviderChain */
        $authProviderChain = $this->getContainer()->get(Services::AUTH_PROVIDER_CHAIN);
        $authProviderChain->addProvider(new LocalAuthProvider());

        $this->getContainer()->register(Services::PASSWORD_STRENGTH_SERVICE, PasswordStrengthService::class);
        $this->getContainer()->register(Services::AUTHENTICATION_SERVICE, AuthenticationService::class);
    }
}
