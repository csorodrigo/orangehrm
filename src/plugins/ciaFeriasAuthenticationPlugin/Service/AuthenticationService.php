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

namespace CiaFerias\Authentication\Service;

use DateTimeInterface;
use CiaFerias\Admin\Traits\Service\UserServiceTrait;
use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\EmployeeTerminationRecord;
use CiaFerias\Entity\User;

class AuthenticationService
{
    use AuthUserTrait;
    use UserServiceTrait;

    /**
     * @param User|null $user
     * @return bool
     * @throws AuthenticationException
     */
    public function setCredentialsForUser(?User $user): bool
    {
        if (!$user instanceof User || $user->isDeleted()) {
            return false;
        } else {
            if (!$user->getStatus()) {
                throw AuthenticationException::userDisabled();
            } elseif ($user->getEmpNumber() === null) {
                throw AuthenticationException::employeeNotAssigned();
            } elseif ($user->getEmployee()->getEmployeeTerminationRecord() instanceof EmployeeTerminationRecord) {
                throw AuthenticationException::employeeTerminated();
            }

            $this->setUserAttributes($user);
            return true;
        }
    }

    /**
     * @param UserCredential $credentials
     * @return bool
     * @throws AuthenticationException
     */
    public function setCredentials(UserCredential $credentials): bool
    {
        $user = $this->getUserService()->getCredentials($credentials);
        return $this->setCredentialsForUser($user);
    }

    /**
     * @param User $user
     */
    protected function setUserAttributes(User $user): void
    {
        $this->getAuthUser()->setUserId($user->getId());
        $this->getAuthUser()->setUserRoleId($user->getUserRole()->getId());
        $this->getAuthUser()->setUserRoleName($user->getUserRole()->getName());
        if ($user->getEmployee() instanceof Employee) {
            $this->getAuthUser()->setEmpNumber($user->getEmployee()->getEmpNumber());
        }
        $lastModified = $user->getDateModified() ?? $user->getDateEntered();
        $this->getAuthUser()->setUserLastModified(
            $lastModified instanceof DateTimeInterface ? $lastModified->format(DateTimeInterface::ATOM) : null
        );
    }
}
