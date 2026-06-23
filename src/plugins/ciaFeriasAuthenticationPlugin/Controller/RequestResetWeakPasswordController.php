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

use CiaFerias\Admin\Service\UserService;
use CiaFerias\Admin\Traits\Service\UserServiceTrait;
use CiaFerias\Authentication\Auth\User as AuthUser;
use CiaFerias\Authentication\Dto\UserCredential;
use CiaFerias\Authentication\Exception\AuthenticationException;
use CiaFerias\Authentication\Traits\CsrfTokenManagerTrait;
use CiaFerias\Authentication\Traits\Service\PasswordStrengthServiceTrait;
use CiaFerias\Authentication\Utility\PasswordStrengthValidation;
use CiaFerias\Core\Api\V2\Exception\InvalidParamException;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Api\V2\Validator\ValidatorException;
use CiaFerias\Core\Traits\LoggerTrait;
use CiaFerias\Core\Traits\ValidatorTrait;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Core\Controller\AbstractController;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Service\ConfigService;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\ORM\EntityManagerHelperTrait;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\Service\TextHelperTrait;
use CiaFerias\Entity\User;
use CiaFerias\Framework\Http\RedirectResponse;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Routing\UrlGenerator;
use CiaFerias\Framework\Services;
use CiaFerias\I18N\Traits\Service\I18NHelperTrait;

class RequestResetWeakPasswordController extends AbstractController implements PublicControllerInterface
{
    use PasswordStrengthServiceTrait;
    use CsrfTokenManagerTrait;
    use UserServiceTrait;
    use AuthUserTrait;
    use I18NHelperTrait;
    use ConfigServiceTrait;
    use TextHelperTrait;
    use LoggerTrait;
    use EntityManagerHelperTrait;
    use ValidatorTrait;
    use PasswordStrengthServiceTrait;

    public const PARAMETER_CURRENT_PASSWORD = 'currentPassword';
    public const PARAMETER_USERNAME = 'username';
    public const PARAMETER_PASSWORD = 'password';
    public const PARAMETER_RESET_CODE = 'resetCode';

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function handle(Request $request)
    {
        $currentPassword = $request->request->get('currentPassword');
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $resetCode = $request->request->get('resetCode');
        $token = $request->request->get('_token');

        $user = $this->getUserService()->geUserDao()->getUserByUserName($username);

        if (!$this->validateParameters($request)) {
            return $this->handleBadRequest();
        }

        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->getContainer()->get(Services::URL_GENERATOR);
        $redirectUrl = $urlGenerator->generate(
            'auth_weak_password_reset',
            ['resetCode' => $resetCode],
            UrlGenerator::ABSOLUTE_URL
        );

        if (!$this->getCsrfTokenManager()->isValid('reset-weak-password', $token)) {
            $this->getAuthUser()->addFlash(
                AuthUser::FLASH_PASSWORD_ENFORCE_ERROR,
                [
                    'error' => AuthenticationException::INVALID_CSRF_TOKEN,
                    'message' => $this->getI18NHelper()->trans('csrf_token_validation_failed'),
                ]
            );
            return new RedirectResponse($redirectUrl);
        }

        if (!$this->getPasswordStrengthService()->validateUrl($resetCode)) {
            $this->getAuthUser()->addFlash(
                AuthUser::FLASH_PASSWORD_ENFORCE_ERROR,
                [
                    'error' => AuthenticationException::INVALID_RESET_CODE,
                    'message' => $this->getI18NHelper()->trans('auth.invalid_password_reset_code')
                ]
            );
            return new RedirectResponse($redirectUrl);
        }

        if (!$user instanceof User || !$this->getUserService()->isCurrentPassword($user->getId(), $currentPassword)) {
            $this->getAuthUser()->addFlash(
                AuthUser::FLASH_PASSWORD_ENFORCE_ERROR,
                [
                    'error' => AuthenticationException::INVALID_CREDENTIALS,
                    'message' => $this->getI18NHelper()->trans('auth.invalid_credentials'),
                ]
            );
            return new RedirectResponse($redirectUrl);
        } else {
            $credentials = new UserCredential($username, $password);
            $this->getPasswordStrengthService()->saveEnforcedPassword($credentials);
            return $this->redirect("auth/login");
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function validateParameters(Request $request): bool
    {
        $variables = $request->request->all();

        $paramRules = $this->getParamRuleCollection();
        $paramRules->addExcludedParamKey('confirmPassword');
        $paramRules->addExcludedParamKey('_token');

        try {
            $credentials = new UserCredential();
            $credentials->setPassword($request->request->get('password'));
            $credentials->setUsername($request->request->get('username'));
            $passwordStrengthValidation = new PasswordStrengthValidation();
            $passwordStrength = $passwordStrengthValidation->checkPasswordStrength($credentials);

            if (!$this->getPasswordStrengthService()->isValidPassword($credentials, $passwordStrength)) {
                return false;
            }

            return $this->validate($variables, $paramRules);
        } catch (InvalidParamException|ValidatorException $e) {
            $this->getLogger()->warning($e->getMessage());
            return false;
        }
    }

    /**
     * @return ParamRuleCollection|null
     */
    private function getParamRuleCollection(): ?ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_USERNAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [
                    UserService::USERNAME_MIN_LENGTH,
                    UserService::USERNAME_MAX_LENGTH
                ])
            ),
            new ParamRule(
                self::PARAMETER_RESET_CODE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::NOT_BLANK),
            ),
            new ParamRule(
                self::PARAMETER_PASSWORD,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [
                    null,
                    ConfigService::MAX_PASSWORD_LENGTH
                ]),
            ),
            new ParamRule(
                self::PARAMETER_CURRENT_PASSWORD,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [
                    null,
                    ConfigService::MAX_PASSWORD_LENGTH
                ]),
            ),
        );
    }
}
