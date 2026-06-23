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

namespace CiaFerias\OAuth\Controller;

use League\OAuth2\Server\Exception\OAuthServerException;
use CiaFerias\Authentication\Auth\User as AuthUser;
use CiaFerias\Authentication\Controller\LoginController;
use CiaFerias\Config\Config;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\LoggerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Services;
use CiaFerias\OAuth\Traits\OAuthServerTrait;
use CiaFerias\OAuth\Traits\PsrHttpFactoryHelperTrait;
use Symfony\Component\HttpFoundation\UrlHelper;
use Throwable;

class AuthorizationController extends AbstractVueController implements PublicControllerInterface
{
    use OAuthServerTrait;
    use PsrHttpFactoryHelperTrait;
    use LoggerTrait;
    use AuthUserTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        if (!$this->getAuthUser()->isAuthenticated()) {
            /** @var UrlHelper $urlHelper */
            $urlHelper = $this->getContainer()->get(Services::URL_HELPER);
            $requestUri = $request->getRequestUri();
            $redirectUri = $urlHelper->getAbsoluteUrl($requestUri);
            $this->getAuthUser()->setAttribute(AuthUser::SESSION_TIMEOUT_REDIRECT_URL, $redirectUri);
            throw new RequestForwardableException(LoginController::class . '::handle');
        }

        $psrRequest = $this->getPsrHttpFactoryHelper()->createPsr7Request($request);
        $component = new Component('oauth-authorize');
        $authRequest = null;
        try {
            $server = $this->getOAuthServer()->getServer();
            $authRequest = $server->validateAuthorizationRequest($psrRequest);
        } catch (OAuthServerException $e) {
            $component->addProp(new Prop('error-type', Prop::TYPE_STRING, $e->getErrorType()));
            $this->getLogger()->error($e->getMessage(), ['hint' => $e->getHint()]);
            $this->getLogger()->error($e->getTraceAsString());
        } catch (Throwable $e) {
            $component->addProp(new Prop('error-type', Prop::TYPE_STRING, 'server_error'));
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());
        }

        $component->addProp(new Prop('params', Prop::TYPE_OBJECT, $psrRequest->getQueryParams()));
        if ($authRequest !== null) {
            $component->addProp(
                new Prop('client-name', Prop::TYPE_STRING, $authRequest->getClient()->getDisplayName())
            );
        }

        $assetsVersion = Config::get(Config::VUE_BUILD_TIMESTAMP);
        $component->addProp(
            new Prop(
                'login-banner-src',
                Prop::TYPE_STRING,
                $request->getBasePath() . "/images/cia-ferias-brand.svg?v=$assetsVersion"
            )
        );

        $this->setComponent($component);
        $this->setTemplate('no_header.html.twig');
    }
}
