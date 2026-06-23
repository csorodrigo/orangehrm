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

namespace CiaFerias\Authentication\Subscriber;

use DateTimeInterface;
use Exception;
use CiaFerias\Admin\Traits\Service\UserServiceTrait;
use CiaFerias\Authentication\Auth\User as AuthUser;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Authentication\Exception\SessionExpiredException;
use CiaFerias\Authentication\Exception\UnauthorizedException;
use CiaFerias\Core\Controller\AbstractModuleController;
use CiaFerias\Core\Controller\AbstractViewController;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Controller\Rest\V2\AbstractRestController;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Entity\User as SystemUser;
use CiaFerias\Framework\Event\AbstractEventSubscriber;
use CiaFerias\Framework\Http\RedirectResponse;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Framework\Http\Session\Session;
use CiaFerias\Framework\Routing\UrlGenerator;
use CiaFerias\Framework\Services;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthenticationSubscriber extends AbstractEventSubscriber
{
    use ServiceContainerTrait;
    use AuthUserTrait;
    use UserServiceTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onRequestEvent', 97000]],
            KernelEvents::CONTROLLER => [['onControllerEvent', 100000]],
            KernelEvents::EXCEPTION => [['onExceptionEvent', 0]],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequestEvent(RequestEvent $event): void
    {
        if (!$this->getAuthUser()->isAuthenticated()) {
            // Stop KernelEvents::REQUEST event propagation and let it throw an exception from AuthenticationSubscriber::onControllerEvent
            $event->stopPropagation();
        }
    }

    /**
     * @param ControllerEvent $event
     * @throws Exception
     */
    public function onControllerEvent(ControllerEvent $event): void
    {
        if ($this->getAuthUser()->isAuthenticated()) {
            $systemUser = $this->getSystemUser();
            $relevantException = $this->resolveAuthenticatedUserException($systemUser);

            if (is_null($relevantException) && $systemUser instanceof SystemUser) {
                if ($this->hasUserLastModifiedChanged($systemUser)) {
                    $relevantException = AuthenticationException::sessionExpired();
                } else {
                    $this->refreshUserLastModified($systemUser);
                }
            }

            if (is_null($relevantException)) {
                return;
            }
            $this->logoutCurrentSession($relevantException);
        }

        if ($this->getControllerInstance($event) instanceof PublicControllerInterface) {
            return;
        }

        if (
            $this->getControllerInstance($event) instanceof AbstractViewController ||
            $this->getControllerInstance($event) instanceof AbstractModuleController
        ) {
            /** @var UrlHelper $urlHelper */
            $urlHelper = $this->getContainer()->get(Services::URL_HELPER);
            $requestUri = $event->getRequest()->getRequestUri();
            $redirectUri = $urlHelper->getAbsoluteUrl($requestUri);
            $this->getAuthUser()->setAttribute(AuthUser::SESSION_TIMEOUT_REDIRECT_URL, $redirectUri);
            throw new SessionExpiredException();
        }

        if ($this->getControllerInstance($event) instanceof AbstractRestController) {
            $response = new Response();
            $message = 'Session expired';
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

        // Fallback
        throw new SessionExpiredException();
    }

    /**
     * @param ExceptionEvent $event
     * @throws Exception
     */
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof SessionExpiredException) {
            /** @var UrlGenerator $urlGenerator */
            $urlGenerator = $this->getContainer()->get(Services::URL_GENERATOR);

            $loginUrl = $urlGenerator->generate('auth_login', [], UrlGenerator::ABSOLUTE_URL);
            $response = new RedirectResponse($loginUrl);

            $event->setResponse($response);
            $event->stopPropagation();
        } elseif ($exception instanceof UnauthorizedException) {
            $event->setResponse($exception->getResponse());
            $event->stopPropagation();
        } elseif ($exception instanceof AuthenticationException) {
            $event->setResponse(
                new Response(
                    json_encode($exception->normalize()),
                    Response::HTTP_UNAUTHORIZED,
                    ['Content-Type' => 'application/json']
                )
            );
            $event->stopPropagation();
        }
    }

    /**
     * @param ControllerEvent $event
     * @return mixed
     */
    private function getControllerInstance(ControllerEvent $event)
    {
        return $event->getController()[0];
    }

    /**
     * @return SystemUser|null
     */
    private function getSystemUser(): ?SystemUser
    {
        $userId = $this->getAuthUser()->getUserId();
        if (is_null($userId)) {
            return null;
        }

        $user = $this->getUserService()->getSystemUser($userId);
        if (!$user instanceof SystemUser) {
            return null;
        }

        return $user;
    }

    /**
     * @param SystemUser $user
     * @return bool
     */
    private function isLoggedInUserActive(SystemUser $user): bool
    {
        if ($user->isDeleted()) {
            return false;
        }

        return $user->getStatus();
    }

    /**
     * @param SystemUser|null $systemUser
     * @return AuthenticationException|null
     */
    private function resolveAuthenticatedUserException(?SystemUser $systemUser): ?AuthenticationException
    {
        if (is_null($systemUser)) {
            return AuthenticationException::noUserFound();
        }

        if (!$this->isLoggedInUserActive($systemUser)) {
            return AuthenticationException::userDisabled();
        }

        if (is_null($systemUser->getEmployee())) {
            return AuthenticationException::employeeNotAssigned();
        }

        if (!is_null($systemUser->getEmployee()->getEmployeeTerminationRecord())) {
            return AuthenticationException::employeeTerminated();
        }

        return null;
    }

    /**
     * @param SystemUser $user
     * @return bool
     */
    private function hasUserLastModifiedChanged(SystemUser $user): bool
    {
        $storedLastModified = $this->getAuthUser()->getUserLastModified();
        $currentLastModified = $this->getUserLastModifiedValue($user);

        if ($storedLastModified === null && $currentLastModified === null) {
            return false;
        }

        return $storedLastModified !== $currentLastModified;
    }

    /**
     * @param SystemUser $user
     */
    private function refreshUserLastModified(SystemUser $user): void
    {
        $this->getAuthUser()->setUserLastModified($this->getUserLastModifiedValue($user));
    }

    /**
     * @param SystemUser $user
     * @return string|null
     */
    private function getUserLastModifiedValue(SystemUser $user): ?string
    {
        $lastModified = $user->getDateModified() ?? $user->getDateEntered();
        return $lastModified instanceof DateTimeInterface ? $lastModified->format(DateTimeInterface::ATOM) : null;
    }

    /**
     * @param AuthenticationException $exception
     */
    private function logoutCurrentSession(AuthenticationException $exception): void
    {
        /** @var Session $session */
        $session = $this->getContainer()->get(Services::SESSION);
        $session->invalidate();
        $this->getAuthUser()->setIsAuthenticated(false);
        $this->getAuthUser()->addFlash(
            AuthUser::FLASH_LOGIN_ERROR,
            $exception->normalize()
        );
    }
}
