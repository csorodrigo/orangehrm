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

namespace CiaFerias\FunctionalTesting\Controller;

use CiaFerias\Authentication\Controller\ValidateController;
use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Service\AuthenticationService;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;

class AuthValidateController extends AbstractController implements PublicControllerInterface
{
    use AuthUserTrait;

    /**
     * @var AuthenticationService|null
     */
    protected ?AuthenticationService $authenticationService = null;

    /**
     * @return AuthenticationService
     */
    public function getAuthenticationService(): AuthenticationService
    {
        if (!$this->authenticationService instanceof AuthenticationService) {
            $this->authenticationService = new AuthenticationService();
        }
        return $this->authenticationService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $username = $request->request->get(ValidateController::PARAMETER_USERNAME, '');
        $password = $request->request->get(ValidateController::PARAMETER_PASSWORD, '');
        $credentials = new UserCredential($username, $password);
        $success = $this->getAuthenticationService()->setCredentials($credentials);
        $this->getAuthUser()->setIsAuthenticated($success);

        $response = $this->getResponse();
        $response->setContent(json_encode(['success' => $success]));
        return $response;
    }
}
