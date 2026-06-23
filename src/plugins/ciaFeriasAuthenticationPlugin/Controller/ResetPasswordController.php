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

namespace CiaFerias\Authentication\Controller;

use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Authentication\Service\ResetPasswordService;
use CiaFerias\Authentication\Traits\CsrfTokenManagerTrait;
use CiaFerias\Core\Controller\AbstractController;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Entity\User;
use CiaFerias\Framework\Http\RedirectResponse;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Services;

class ResetPasswordController extends AbstractController implements PublicControllerInterface
{
    use CsrfTokenManagerTrait;

    protected ?ResetPasswordService $resetPasswordService = null;

    /**
     * @return ResetPasswordService
     */
    public function getResetPasswordService(): ResetPasswordService
    {
        if (!$this->resetPasswordService instanceof ResetPasswordService) {
            $this->resetPasswordService = new ResetPasswordService();
        }
        return $this->resetPasswordService;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function handle(Request $request): RedirectResponse
    {
        $token = $request->request->get('_token');
        $resetCode = $request->request->get('resetCode');

        if (!$this->getCsrfTokenManager()->isValid('reset-password', $token)) {
            throw AuthenticationException::invalidCsrfToken();
        }
        $user = $this->getResetPasswordService()->validateUrl((string)$resetCode);
        if (!$user instanceof User) {
            throw AuthenticationException::invalidResetCode();
        }
        $username = $request->request->get('username');
        if ($user->getUserName() !== $username) {
            throw AuthenticationException::invalidResetCode();
        }
        $password = $request->request->get('password');
        $credentials = new UserCredential($user->getUserName(), $password);
        $this->getResetPasswordService()->saveResetPassword($credentials);
        $session = $this->getContainer()->get(Services::SESSION);
        $session->invalidate();
        return $this->redirect("auth/login");
    }
}
