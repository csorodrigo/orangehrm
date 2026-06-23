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

namespace CiaFerias\Leave\Api;

use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\CollectionEndpoint;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointCollectionResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Leave\Api\Model\LeaveTypeModel;
use CiaFerias\Leave\Traits\Service\LeaveTypeServiceTrait;

class EligibleLeaveTypeAPI extends Endpoint implements CollectionEndpoint
{
    use LeaveTypeServiceTrait;
    use AuthUserTrait;

    public const PARAMETER_INCLUDE_ALLOCATED = 'includeAllocated';

    /**
     * @OA\Get(
     *     path="/api/v2/leave/leave-types/eligible",
     *     tags={"Leave/Leave Type"},
     *     summary="Get My Eligible Leave Types",
     *     operationId="get-my-eligible-leave-types",
     *     @OA\Parameter(
     *         name="empNumber",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="includeAllocated",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", default="false")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Leave-LeaveTypeModel"
     *             ),
     *             @OA\Property(property="meta",
     *                 type="object",
     *                 @OA\Property(property="empNumber", type="integer")
     *             )
     *         )
     *     ),
     * )
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_QUERY,
            CommonParams::PARAMETER_EMP_NUMBER,
            $this->getAuthUser()->getEmpNumber()
        );
        $includeAllocated = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_INCLUDE_ALLOCATED,
            false
        );
        $leaveTypes = $this->getLeaveTypeService()->getEligibleLeaveTypesByEmpNumber($empNumber, $includeAllocated);
        return new EndpointCollectionResult(
            LeaveTypeModel::class,
            $leaveTypes,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_EMP_NUMBER,
                    new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
                )
            ),
            new ParamRule(
                self::PARAMETER_INCLUDE_ALLOCATED,
                new Rule(Rules::BOOL_VAL)
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
