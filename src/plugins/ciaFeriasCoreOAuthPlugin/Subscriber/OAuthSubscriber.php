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

namespace CiaFerias\OAuth\Subscriber;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use CiaFerias\Admin\Traits\Service\UserServiceTrait;
use CiaFerias\Authentication\Exception\SessionExpiredException;
use CiaFerias\Authentication\Exception\UnauthorizedException;
use CiaFerias\Authentication\Service\AuthenticationService;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\LoggerTrait;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Entity\OAuthAccessToken;
use CiaFerias\Framework\Event\AbstractEventSubscriber;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Framework\Http\Session\Session;
use CiaFerias\Framework\Services;
use CiaFerias\OAuth\Dto\CryptKey;
use CiaFerias\OAuth\Repository\AccessTokenRepository;
use CiaFerias\OAuth\Server\BearerTokenValidator;
use CiaFerias\OAuth\Traits\PsrHttpFactoryHelperTrait;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class OAuthSubscriber extends AbstractEventSubscriber
{
    use AuthUserTrait;
    use PsrHttpFactoryHelperTrait;
    use UserServiceTrait;
    use ConfigServiceTrait;
    use LoggerTrait;

    private AuthenticationService $authenticationService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onRequestEvent', 98000]],
            KernelEvents::RESPONSE => [['onResponseEvent', 0]],
        ];
    }

    /**
     * @return AuthenticationService
     */
    private function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService ??= new AuthenticationService();
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequestEvent(RequestEvent $event): void
    {
        if (!$this->getAuthUser()->isAuthenticated() && $event->getRequest()->headers->has('authorization')) {
            // Attempt to check OAuth token

            $tokenEncryptionKey = $this->getConfigService()->getOAuthTokenEncryptionKey();
            $accessTokenRepository = new AccessTokenRepository();
            $server = new ResourceServer(
                $accessTokenRepository,
                new CryptKey(), // We are using opaque token, not JWT
                new BearerTokenValidator($accessTokenRepository, $tokenEncryptionKey),
            );

            try {
                $request = $this->getPsrHttpFactoryHelper()->createPsr7Request($event->getRequest());
                $request = $server->validateAuthenticatedRequest($request);

                /** @var OAuthAccessToken $accessToken */
                $accessToken = $request->getAttribute(BearerTokenValidator::ATTRIBUTE_ACCESS_TOKEN);
                $user = $this->getUserService()->geUserDao()->getSystemUser($accessToken->getUserId());

                $this->getAuthenticationService()->setCredentialsForUser($user);
                $this->getAuthUser()->setIsAuthenticated(true);
            } catch (Throwable $e) {
                $this->getLogger()->error($e->getMessage(), $e instanceof OAuthServerException ? [$e->getHint()] : []);
                $this->handleException($event, $e);
            }
        }
    }

    /**
     * @param RequestEvent $event
     * @param Throwable $e
     * @throws SessionExpiredException
     * @throws UnauthorizedException
     */
    private function handleException(RequestEvent $event, Throwable $e): void
    {
        $request = $event->getRequest();

        // 'application/json', 'application/x-json'
        if ($request->getContentType() === 'json' || $request->headers->get('accept') === 'application/json') {
            $response = new Response();
            $message = $e instanceof OAuthServerException
                ? $e->getMessage()
                : 'Unexpected error occurred while evaluating the `Bearer` token';
            $response->setContent(
                \CiaFerias\Core\Api\V2\Response::formatError(
                    ['error' => ['status' => Response::HTTP_UNAUTHORIZED, 'message' => $message]]
                )
            );
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->headers->set(
                \CiaFerias\Core\Api\V2\Response::CONTENT_TYPE_KEY,
                \CiaFerias\Core\Api\V2\Response::CONTENT_TYPE_JSON
            );
            throw new UnauthorizedException($response, $message);
        }

        throw new SessionExpiredException();
    }

    /**
     * @param ResponseEvent $event
     */
    public function onResponseEvent(ResponseEvent $event): void
    {
        if ($event->getRequest()->headers->has('authorization') && $this->getAuthUser()->isAuthenticated()) {
            /** @var Session $session */
            $session = $this->getContainer()->get(Services::SESSION);
            $session->invalidate();
        }
    }
}
