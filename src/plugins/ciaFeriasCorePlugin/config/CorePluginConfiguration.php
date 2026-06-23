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

use CiaFerias\Config\Config;
use CiaFerias\Core\Authorization\Helper\UserRoleManagerHelper;
use CiaFerias\Core\Authorization\Manager\UserRoleManagerFactory;
use CiaFerias\Core\Command\EnableTestLanguagePackCommand;
use CiaFerias\Core\Command\RunScheduleCommand;
use CiaFerias\Core\Helper\ClassHelper;
use CiaFerias\Core\Registration\Subscriber\RegistrationEventPersistSubscriber;
use CiaFerias\Core\Service\CacheService;
use CiaFerias\Core\Service\ConfigService;
use CiaFerias\Core\Service\DateTimeHelperService;
use CiaFerias\Core\Service\MenuService;
use CiaFerias\Core\Service\ModuleService;
use CiaFerias\Core\Service\NormalizerService;
use CiaFerias\Core\Service\NumberHelperService;
use CiaFerias\Core\Service\ReportGeneratorService;
use CiaFerias\Core\Service\TextHelperService;
use CiaFerias\Core\Subscriber\ApiAuthorizationSubscriber;
use CiaFerias\Core\Subscriber\ExceptionSubscriber;
use CiaFerias\Core\Subscriber\GlobalConfigSubscriber;
use CiaFerias\Core\Subscriber\MailerSubscriber;
use CiaFerias\Core\Subscriber\ModuleNotAvailableSubscriber;
use CiaFerias\Core\Subscriber\RequestBodySubscriber;
use CiaFerias\Core\Subscriber\RequestForwardableExceptionSubscriber;
use CiaFerias\Core\Subscriber\ScreenAuthorizationSubscriber;
use CiaFerias\Core\Subscriber\SessionSubscriber;
use CiaFerias\Core\Traits\EventDispatcherTrait;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Framework\Console\Console;
use CiaFerias\Framework\Console\ConsoleConfigurationInterface;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Session\MemorySessionStorage;
use CiaFerias\Framework\Http\Session\NativeSessionStorage;
use CiaFerias\Framework\Http\Session\Session;
use CiaFerias\Framework\PluginConfigurationInterface;
use CiaFerias\Framework\Services;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpKernel\EventListener\SessionListener;
use Symfony\Component\HttpKernel\KernelEvents;

class CorePluginConfiguration implements PluginConfigurationInterface, ConsoleConfigurationInterface
{
    use ServiceContainerTrait;
    use EventDispatcherTrait;
    use ConfigServiceTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $sessionStorage = $this->getSessionStorage($request);
        $session = new Session($sessionStorage);
        $session->start();

        $this->getContainer()->set(Services::SESSION_STORAGE, $sessionStorage);
        $this->getContainer()->set(Services::SESSION, $session);
        $this->getContainer()->register(Services::CONFIG_SERVICE, ConfigService::class);
        $this->getContainer()->register(Services::NORMALIZER_SERVICE, NormalizerService::class);
        $this->getContainer()->register(Services::DATETIME_HELPER_SERVICE, DateTimeHelperService::class);
        $this->getContainer()->register(Services::TEXT_HELPER_SERVICE, TextHelperService::class);
        $this->getContainer()->register(Services::NUMBER_HELPER_SERVICE, NumberHelperService::class);
        $this->getContainer()->register(Services::CLASS_HELPER, ClassHelper::class);
        $this->getContainer()->register(Services::USER_ROLE_MANAGER)
            ->setFactory([UserRoleManagerFactory::class, 'getUserRoleManager']);
        $this->getContainer()->register(Services::USER_ROLE_MANAGER_HELPER, UserRoleManagerHelper::class);
        $this->getContainer()->register(Services::CACHE)->setFactory([CacheService::class, 'getCache']);
        $this->getContainer()->register(Services::MENU_SERVICE, MenuService::class);
        $this->getContainer()->register(Services::MODULE_SERVICE, ModuleService::class);
        $this->getContainer()->register(Services::REPORT_GENERATOR_SERVICE, ReportGeneratorService::class);

        $this->registerCoreSubscribers();
    }

    private function registerCoreSubscribers(): void
    {
        $this->getEventDispatcher()->addSubscriber(new ExceptionSubscriber());
        $this->getEventDispatcher()->addListener(
            KernelEvents::REQUEST,
            [new SessionListener($this->getContainer()), 'onKernelRequest'],
        );
        $this->getEventDispatcher()->addSubscriber(new SessionSubscriber());
        $this->getEventDispatcher()->addSubscriber(new RequestForwardableExceptionSubscriber());
        $this->getEventDispatcher()->addSubscriber(new ScreenAuthorizationSubscriber());
        $this->getEventDispatcher()->addSubscriber(new ApiAuthorizationSubscriber());
        $this->getEventDispatcher()->addSubscriber(new RequestBodySubscriber());
        $this->getEventDispatcher()->addSubscriber(new MailerSubscriber());
        $this->getEventDispatcher()->addSubscriber(new ModuleNotAvailableSubscriber());
        $this->getEventDispatcher()->addSubscriber(new GlobalConfigSubscriber());
        if ($this->getConfigService()->getInstanceIdentifier() !== null) {
            $this->getEventDispatcher()->addSubscriber(new RegistrationEventPersistSubscriber());
        }
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Console $console): void
    {
        $console->add(new RunScheduleCommand());
        if (Config::PRODUCT_MODE !== Config::MODE_PROD) {
            $console->add(new EnableTestLanguagePackCommand());
        }
    }

    /**
     * @param Request $request
     * @return SessionStorageInterface
     */
    private function getSessionStorage(Request $request): SessionStorageInterface
    {
        if ($request->headers->has('authorization')) {
            // To reduce session IO operations, handle in-memory session storage for token based clients
            return new MemorySessionStorage();
        }
        $isSecure = $request->isSecure();
        $path = $request->getBasePath();
        $options = [
            'name' => $isSecure ? 'cia_ferias' : '_cia_ferias',
            'cookie_secure' => $isSecure,
            'cookie_httponly' => true,
            'cookie_path' => $path == '' ? '/' : $path,
            'cookie_samesite' => 'Lax',
        ];
        return new NativeSessionStorage(
            $options,
            new NativeFileSessionHandler(Config::get(Config::SESSION_DIR))
        );
    }
}
