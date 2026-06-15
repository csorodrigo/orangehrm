<?php

namespace OrangeHRM\Leave\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayCollectionModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Leave\Dao\VacationPlanningDao;
use OrangeHRM\Leave\Service\VacationPlanningJudgeService;

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
        $subunitId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::FILTER_SUBUNIT_ID
        );

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
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::FILTER_SUBUNIT_ID, new Rule(Rules::POSITIVE))
            )
        );
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
