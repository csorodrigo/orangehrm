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

namespace CiaFerias\Dashboard\Api;

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
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\Service\DateTimeHelperTrait;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Dashboard\Api\Model\EmployeesOnLeaveListModel;
use CiaFerias\Entity\Employee;
use CiaFerias\Leave\Traits\Service\LeaveConfigServiceTrait;
use CiaFerias\Dashboard\Dto\EmployeeOnLeaveSearchFilterParams;
use CiaFerias\Dashboard\Traits\Service\EmployeeOnLeaveServiceTrait;

class EmployeeOnLeaveAPI extends Endpoint implements CollectionEndpoint
{
    use DateTimeHelperTrait;
    use LeaveConfigServiceTrait;
    use EmployeeOnLeaveServiceTrait;
    use ConfigServiceTrait;
    use UserRoleManagerTrait;
    use AuthUserTrait;

    public const PARAMETER_DATE = 'date';
    public const META_PARAMETER_LEAVE_PERIOD_DEFINED =  'leavePeriodDefined';

    /**
     * @OA\Get(
     *     path="/api/v2/dashboard/employees/leaves",
     *     tags={"Dashboard/Widgets"},
     *     summary="Get Employees on Leave Today",
     *     operationId="get-employees-on-leave-today",
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=EmployeeOnLeaveSearchFilterParams::ALLOWED_SORT_FIELDS)
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Dashboard-EmployeeOnLeaveListModel")
     *             ),
     *             @OA\Property(property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="leavePeriodDefined", type="boolean"),
     *             )
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $employeeOnLeaveSearchFilterParams = new EmployeeOnLeaveSearchFilterParams();

        $this->setSortingAndPaginationParams($employeeOnLeaveSearchFilterParams);
        $date = $this->getRequestParams()->getDateTime(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_DATE,
            null,
            $this->getDateTimeHelper()->getNow()
        );

        $employeeOnLeaveSearchFilterParams->setDate($date);

        $showOnlyAccessibleEmployeesOnLeaveToday = $this->getConfigService()
            ->getDashboardEmployeesOnLeaveTodayShowOnlyAccessibleConfig();

        if ($showOnlyAccessibleEmployeesOnLeaveToday) {
            $accessibleEmpNumbers = $this->getUserRoleManager()->getAccessibleEntityIds(Employee::class);
            $employeeOnLeaveSearchFilterParams->setAccessibleEmpNumber([$this->getAuthUser()->getEmpNumber(),...$accessibleEmpNumbers]);
        }

        $leavePeriodDefined = $this->getLeaveConfigService()->isLeavePeriodDefined();

        $empLeaveList = $this->getEmployeeOnLeaveService()->getEmployeeOnLeaveDao()
            ->getEmployeeOnLeaveList($employeeOnLeaveSearchFilterParams);
        $employeeCount = $this->getEmployeeOnLeaveService()->getEmployeeOnLeaveDao()
            ->getEmployeeOnLeaveCount($employeeOnLeaveSearchFilterParams);

        return new EndpointCollectionResult(
            EmployeesOnLeaveListModel::class,
            [$empLeaveList],
            new ParameterBag([
                CommonParams::PARAMETER_TOTAL => $employeeCount,
                self::META_PARAMETER_LEAVE_PERIOD_DEFINED => $leavePeriodDefined,
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_DATE,
                new Rule(Rules::API_DATE)
            ),
            ...$this->getSortingAndPaginationParamsRules(EmployeeOnLeaveSearchFilterParams::ALLOWED_SORT_FIELDS),
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
