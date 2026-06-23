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

namespace CiaFerias\Core\Api\V2\Validator\Rules;

use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Traits\Service\PasswordStrengthServiceTrait;
use CiaFerias\Authentication\Utility\PasswordStrengthValidation;
use CiaFerias\Core\Traits\Service\TextHelperTrait;

class Password extends AbstractRule
{
    use TextHelperTrait;
    use PasswordStrengthServiceTrait;

    private bool $changePassword;

    public function __construct(?bool $changePassword)
    {
        $this->changePassword = $changePassword ?? true;
    }

    public function validate($input): bool
    {
        if (!$this->changePassword) {
            return true;
        }

        $passwordStrengthValidation = new PasswordStrengthValidation();
        $credentials = new UserCredential(null, $input);

        $passwordStrength = $passwordStrengthValidation->checkPasswordStrength($credentials);
        $messages = $this->getPasswordStrengthService()->checkPasswordPolicies($credentials, $passwordStrength);

        if (count($messages) === 0) {
            return true;
        }
        return false;
    }
}
