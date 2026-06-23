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

namespace CiaFerias\Authentication\Auth;

use CiaFerias\Authentication\Dto\AuthParamsInterface;
use CiaFerias\Authentication\Dto\UserCredentialInterface;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Authentication\Exception\PasswordEnforceException;
use CiaFerias\Authentication\Service\AuthenticationService;
use CiaFerias\Authentication\Traits\Service\PasswordStrengthServiceTrait;
use CiaFerias\Authentication\Utility\PasswordStrengthValidation;
use CiaFerias\Core\Service\ConfigService;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Framework\Services;
use CiaFerias\I18N\Traits\Service\I18NHelperTrait;

class LocalAuthProvider extends AbstractAuthProvider
{
    use ConfigServiceTrait;
    use PasswordStrengthServiceTrait;
    use I18NHelperTrait;

    private AuthenticationService $authenticationService;

    /**
     * @return AuthenticationService
     */
    private function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService ??= new AuthenticationService();
    }

    /**
     * @param AuthParamsInterface $authParams
     * @return bool
     * @throws AuthenticationException
     * @throws PasswordEnforceException
     */
    public function authenticate(AuthParamsInterface $authParams): bool
    {
        if (!$authParams->getCredential() instanceof UserCredentialInterface) {
            return false;
        }
        $success = $this->getAuthenticationService()->setCredentials($authParams->getCredential());
        if ($success) {
            if ($this->getConfigService()->getConfigDao()
                    ->getValue(ConfigService::KEY_ENFORCE_PASSWORD_STRENGTH) === 'on') {
                $passwordStrengthValidation = new PasswordStrengthValidation();
                $passwordStrength = $passwordStrengthValidation->checkPasswordStrength(
                    $authParams->getCredential()
                );

                if (!($this->getPasswordStrengthService()
                    ->isValidPassword($authParams->getCredential(), $passwordStrength))
                ) {
                    $e = new PasswordEnforceException(
                        AuthenticationException::PASSWORD_NOT_STRONG,
                        $this->getI18NHelper()->trans('password_not_strong'),
                    );
                    $e->generateResetCode();

                    $session = $this->getContainer()->get(Services::SESSION);
                    $session->invalidate();
                    throw $e;
                }
            }
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 10000;
    }
}
