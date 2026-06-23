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

use CiaFerias\Core\Traits\EventDispatcherTrait;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\PluginConfigurationInterface;
use CiaFerias\Framework\Services;
use CiaFerias\OAuth\Server\OAuthServer;
use CiaFerias\OAuth\Service\PsrHttpFactoryHelper;
use CiaFerias\OAuth\Subscriber\OAuthSubscriber;
use CiaFerias\OAuth\Service\OAuthService;

class CoreOAuthPluginConfiguration implements PluginConfigurationInterface
{
    use ServiceContainerTrait;
    use EventDispatcherTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $this->getContainer()->register(Services::PSR_HTTP_FACTORY_HELPER, PsrHttpFactoryHelper::class);
        $this->getContainer()->register(Services::OAUTH_SERVER, OAuthServer::class);
        $this->getContainer()->register(Services::OAUTH_SERVICE, OAuthService::class);

        $this->getEventDispatcher()->addSubscriber(new OAuthSubscriber());
    }
}
