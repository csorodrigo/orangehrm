<?php

namespace CiaFerias\Leave\Api;

use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\CollectionEndpoint;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointCollectionResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\Model\ArrayCollectionModel;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Leave\Dao\VacationPlanningDao;
use CiaFerias\Leave\Service\VacationPlanningJudgeService;

class VacationPlanningAPI extends Endpoint implements CollectionEndpoint
{
    public const FILTER_SUBUNIT_ID = 'subunitId';

    protected ?VacationPlanningDao $vacationPlanningDao = null;
    protected ?VacationPlanningJudgeService $vacationPlanningJudgeService = null;

    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        if ($empNumber === 0) {
            $empNumber = null;
        }
        $subunitId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_SUBUNIT_ID
        );
        if ($subunitId === 0) {
            $subunitId = null;
        }

        $employees = $this->getVacationPlanningDao()->getPlanningEmployees($empNumber, $subunitId);
        $plans = $this->getVacationPlanningJudgeService()->buildPlan($employees);
        $aboveNine = count(array_filter($plans, fn (array $plan) => $plan['score'] >= 9.0));
        $eligible = count(array_filter($plans, fn (array $plan) => $plan['risk'] !== 'nao_elegivel'));

        return new EndpointCollectionResult(
            ArrayCollectionModel::class,
            $plans,
            new ParameterBag(
                [
                    CommonParams::PARAMETER_TOTAL => count($plans),
                    'eligible' => $eligible,
                    'aboveNine' => $aboveNine,
                    'aboveNineRate' => $eligible > 0 ? round(($aboveNine / $eligible) * 100, 1) : 0,
                ]
            )
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        $paramRuleCollection = new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::ZERO_OR_POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_SUBUNIT_ID, new Rule(Rules::ZERO_OR_POSITIVE))
            ),
            ...$this->getSortingAndPaginationParamsRules([], true)
        );

        $paramRuleCollection->setStrict(false);
        return $paramRuleCollection;
    }

    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    protected function getVacationPlanningDao(): VacationPlanningDao
    {
        if (!$this->vacationPlanningDao instanceof VacationPlanningDao) {
            $this->vacationPlanningDao = new VacationPlanningDao();
        }
        return $this->vacationPlanningDao;
    }

    protected function getVacationPlanningJudgeService(): VacationPlanningJudgeService
    {
        if (!$this->vacationPlanningJudgeService instanceof VacationPlanningJudgeService) {
            $this->vacationPlanningJudgeService = new VacationPlanningJudgeService();
        }
        return $this->vacationPlanningJudgeService;
    }
}
