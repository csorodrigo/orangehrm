<?php

namespace CiaFerias\Leave\Api;

use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\Model\ArrayModel;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\ResourceEndpoint;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Leave\Dao\VacationPlanningDao;

class VacationPreferenceAPI extends Endpoint implements ResourceEndpoint
{
    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_OPTION_A = 'optionA';
    public const PARAMETER_OPTION_B = 'optionB';
    public const PARAMETER_OPTION_C = 'optionC';
    public const PARAMETER_RESTRICTED_MONTH = 'restrictedMonth';

    protected ?VacationPlanningDao $vacationPlanningDao = null;

    public function getOne(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_EMP_NUMBER
        );

        return new EndpointResourceResult(ArrayModel::class, $this->getVacationPlanningDao()->getPreference($empNumber));
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))
        );
    }

    public function update(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_EMP_NUMBER
        );

        $preference = [
            'optionA' => $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_OPTION_A, []),
            'optionB' => $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_OPTION_B, []),
            'optionC' => $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_OPTION_C, []),
            'restrictedMonth' => $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_RESTRICTED_MONTH
            ),
        ];

        return new EndpointResourceResult(
            ArrayModel::class,
            $this->getVacationPlanningDao()->savePreference($empNumber, $preference)
        );
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_OPTION_A, new Rule(Rules::ARRAY_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_OPTION_B, new Rule(Rules::ARRAY_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_OPTION_C, new Rule(Rules::ARRAY_TYPE))
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(self::PARAMETER_RESTRICTED_MONTH, new Rule(Rules::IN, [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]]))
            )
        );
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
}
