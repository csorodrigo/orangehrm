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

namespace CiaFerias\Core\Subscriber;

use CiaFerias\Core\Api\V2\Exception\BadRequestException;
use CiaFerias\Core\Api\V2\Exception\ForbiddenException;
use CiaFerias\Core\Api\V2\Exception\NotImplementedException;
use CiaFerias\Core\Api\V2\Exception\RecordNotFoundException;
use CiaFerias\Framework\Event\AbstractEventSubscriber;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber extends AbstractEventSubscriber
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onExceptionEvent', -65],
            ],
        ];
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onExceptionEvent(ExceptionEvent $event)
    {
        //new \Symfony\Component\HttpKernel\EventListener\ErrorListener()
        $exception = $event->getThrowable();
        $response = $event->hasResponse() ? $event->getResponse() : new Response();

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } elseif ($exception instanceof BadRequestException) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } elseif ($exception instanceof RecordNotFoundException) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        } elseif ($exception instanceof ForbiddenException) {
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } elseif ($exception instanceof NotImplementedException) {
            $response->setStatusCode(Response::HTTP_NOT_IMPLEMENTED);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
