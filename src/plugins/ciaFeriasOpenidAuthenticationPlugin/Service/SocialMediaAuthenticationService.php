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

namespace CiaFerias\OpenidAuthentication\Service;

use CiaFerias\Admin\Dao\UserDao;
use CiaFerias\Admin\Dto\UserSearchFilterParams;
use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Authentication\Service\AuthenticationService;
use CiaFerias\Authentication\Traits\Service\AuthenticationServiceTrait;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Utility\EncryptionHelperTrait;
use CiaFerias\Entity\AuthProviderExtraDetails;
use CiaFerias\Entity\EmployeeTerminationRecord;
use CiaFerias\Entity\OpenIdProvider;
use CiaFerias\Entity\OpenIdUserIdentity;
use CiaFerias\Entity\User;
use CiaFerias\Framework\Routing\UrlGenerator;
use CiaFerias\Framework\Services;
use CiaFerias\OpenidAuthentication\Dao\AuthProviderDao;
use CiaFerias\OpenidAuthentication\Dto\ProviderSearchFilterParams;
use CiaFerias\OpenidAuthentication\OpenID\OpenIDConnectClient;
use CiaFerias\OpenidAuthentication\Traits\Service\SocialMediaAuthenticationServiceTrait;

class SocialMediaAuthenticationService
{
    use SocialMediaAuthenticationServiceTrait;
    use AuthenticationServiceTrait;
    use EncryptionHelperTrait;
    use AuthUserTrait;

    private AuthenticationService $authenticationService;
    private AuthProviderDao $authProviderDao;
    private UserDao $userDao;

    public const SCOPE = 'email';

    /**
     * @return AuthProviderDao
     */
    public function getAuthProviderDao(): AuthProviderDao
    {
        return $this->authProviderDao ??= new AuthProviderDao();
    }

    /**
     * @return UserDao
     */
    public function getUserDao(): UserDao
    {
        return $this->userDao ??= new UserDao();
    }

    /**
     * @param AuthProviderExtraDetails $provider
     * @param string $scope
     * @param string $redirectUrl
     *
     * @return OpenIDConnectClient
     */
    public function initiateAuthentication(AuthProviderExtraDetails $provider, string $scope, string $redirectUrl): OpenIDConnectClient
    {
        $oidcClient = new OpenIDConnectClient(
            $provider->getOpenIdProvider()->getProviderUrl(),
            $provider->getClientId(),
            self::encryptionEnabled()
                ? self::getCryptographer()->decrypt($provider->getClientSecret())
                : $provider->getClientSecret(),
        );

        $oidcClient->addScope([$scope]);
        $oidcClient->setRedirectURL($redirectUrl);

        return $oidcClient;
    }

    /**
     * @return string
     */
    public function getRedirectURL(): string
    {
        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->getContainer()->get(Services::URL_GENERATOR);
        return $urlGenerator->generate('auth_oidc_login_redirect', [], UrlGenerator::ABSOLUTE_URL);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return self::SCOPE;
    }

    /**
     * @param UserCredential $userCredential
     * @return User[]
     */
    private function getSystemUsers(UserCredential $userCredential): array
    {
        $userSearchFilterParams = new UserSearchFilterParams();
        $userSearchFilterParams->setUsername($userCredential->getUsername());

        return $this->getUserDao()->searchSystemUsers($userSearchFilterParams);
    }

    /**
     * @param UserCredential $userCredentials
     *
     * @return User
     * @throws AuthenticationException
     */
    public function getUserForAuthenticate(UserCredential $userCredentials): User
    {
        $users = $this->getSystemUsers($userCredentials);
        if (empty($users)) {
            throw AuthenticationException::noUserFound();
        }

        if (sizeof($users) > 1) {
            throw AuthenticationException::multipleUserReturned();
        }

        $user = $users[0];

        if (!$user instanceof User || $user->isDeleted()) {
            throw AuthenticationException::invalidCredentials();
        } else {
            if (!$user->getStatus()) {
                throw AuthenticationException::userDisabled();
            } elseif ($user->getEmpNumber() === null) {
                throw AuthenticationException::employeeNotAssigned();
            } elseif ($user->getEmployee()->getEmployeeTerminationRecord() instanceof EmployeeTerminationRecord) {
                throw AuthenticationException::employeeTerminated();
            }
            return $user;
        }
    }

    /**
     * @param User $user
     * @param OpenIdProvider $provider
     *
     * @return OpenIdUserIdentity
     */
    public function setOIDCUserIdentity(User $user, OpenIdProvider $provider): OpenIdUserIdentity
    {
        $openIdUserIdentity = new OpenIdUserIdentity();
        $openIdUserIdentity->setUser($user);
        $openIdUserIdentity->setOpenIdProvider($provider);

        return $this->getAuthProviderDao()->saveUserIdentity($openIdUserIdentity);
    }

    /**
     * @param User $user
     *
     * @return bool
     * @throws AuthenticationException
     */
    public function handleOIDCAuthentication(User $user): bool
    {
        return $this->getAuthenticationService()->setCredentialsForUser($user);
    }

    /**
     * @return bool
     */
    public function isSocialMediaAuthEnable(): bool
    {
        $providerSearchFilterParams = new ProviderSearchFilterParams();
        $providerSearchFilterParams->setName(null);
        $providerSearchFilterParams->setStatus(true);

        $count = $this->getAuthProviderDao()->getAuthProviderCount($providerSearchFilterParams);
        return $count > 0;
    }
}
