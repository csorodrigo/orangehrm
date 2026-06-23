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

namespace CiaFerias\Recruitment\Controller\PublicController;

use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\Exception\NotImplementedException;
use CiaFerias\Core\Api\V2\Request;
use CiaFerias\Core\Api\V2\Response;
use CiaFerias\Core\Api\V2\Validator\Helpers\ValidationDecorator;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Controller\Rest\V2\AbstractRestController;
use CiaFerias\Core\Dto\FilterParams;
use CiaFerias\Core\Exception\SearchParamException;
use CiaFerias\Core\Traits\Service\NormalizerServiceTrait;
use CiaFerias\ORM\ListSorter;
use CiaFerias\Recruitment\Api\Model\VacancyModel;
use CiaFerias\Recruitment\Dto\VacancySearchFilterParams;
use CiaFerias\Recruitment\Traits\Service\VacancyServiceTrait;

class VacancyListRestController extends AbstractRestController implements PublicControllerInterface
{
    use VacancyServiceTrait;
    use NormalizerServiceTrait;

    private const VACANCY_ID = 'vacancy.id';
    private const VACANCY_OFFSET = 'offset';
    private const VACANCY_LIMIT = 'limit';
    /**
     * @var ValidationDecorator|null
     */
    private ?ValidationDecorator $validationDecorator = null;


    /**
     * @param Request $request
     * @return Response
     * @throws SearchParamException
     */
    public function handleGetRequest(Request $request): Response
    {
        $offset = $request->getQuery()->get(self::VACANCY_OFFSET, FilterParams::DEFAULT_OFFSET);
        $limit = $request->getQuery()->get(self::VACANCY_LIMIT, FilterParams::DEFAULT_LIMIT);
        $vacancySearchFilterParams = new VacancySearchFilterParams();
        $vacancySearchFilterParams->setStatus(true);
        $vacancySearchFilterParams->setIsPublished(true);
        $vacancySearchFilterParams->setSortField(self::VACANCY_ID);
        $vacancySearchFilterParams->setSortOrder(ListSorter::DESCENDING);
        $vacancySearchFilterParams->setLimit($limit);
        $vacancySearchFilterParams->setOffset($offset);
        $vacancies = $this->getVacancyService()->getVacancyDao()->getVacancies($vacancySearchFilterParams);
        $count = $this->getVacancyService()->getVacancyDao()->getVacanciesCount($vacancySearchFilterParams);

        return new Response(
            $this->getNormalizerService()
                ->normalizeArray(VacancyModel::class, $vacancies),
            [CommonParams::PARAMETER_TOTAL => $count]
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws NotImplementedException
     */
    public function handlePostRequest(Request $request): Response
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @return NotImplementedException
     */
    private function getNotImplementedException(): NotImplementedException
    {
        return new NotImplementedException();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws NotImplementedException
     */
    public function handlePutRequest(Request $request): Response
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws NotImplementedException
     */
    public function handleDeleteRequest(Request $request): Response
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @param Request $request
     * @return ParamRuleCollection|null
     */
    protected function initGetValidationRule(Request $request): ?ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_LIMIT,
                    new Rule(Rules::ZERO_OR_POSITIVE), // Zero for not to limit results
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_OFFSET,
                    new Rule(Rules::ZERO_OR_POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_SORT_ORDER,
                    new Rule(Rules::IN, [[ListSorter::ASCENDING, ListSorter::DESCENDING]])
                ),
                true
            )
        );
    }

    /**
     * @return ValidationDecorator
     */
    protected function getValidationDecorator(): ValidationDecorator
    {
        if (!$this->validationDecorator instanceof ValidationDecorator) {
            $this->validationDecorator = new ValidationDecorator();
        }
        return $this->validationDecorator;
    }

    /**
     * @param Request $request
     * @return ParamRuleCollection|null
     * @throws NotImplementedException
     */
    public function initPostValidationRule(Request $request): ?ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @param Request $request
     * @return ParamRuleCollection|null
     * @throws NotImplementedException
     */
    public function initPutValidationRule(Request $request): ?ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @param Request $request
     * @return ParamRuleCollection|null
     * @throws NotImplementedException
     */
    public function initDeleteValidationRule(Request $request): ?ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
