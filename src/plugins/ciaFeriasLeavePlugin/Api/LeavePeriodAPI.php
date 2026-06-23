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
use CiaFerias\Core\Api\V2\CrudEndpoint;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointCollectionResult;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Service\MenuService;
use CiaFerias\Core\Traits\Service\DateTimeHelperTrait;
use CiaFerias\Core\Traits\Service\NormalizerServiceTrait;
use CiaFerias\Entity\LeavePeriodHistory;
use CiaFerias\Framework\Services;
use CiaFerias\Leave\Api\Model\LeavePeriodHistoryModel;
use CiaFerias\Leave\Api\Model\LeavePeriodModel;
use CiaFerias\Leave\Traits\Service\LeaveConfigServiceTrait;
use CiaFerias\Leave\Traits\Service\LeavePeriodServiceTrait;

class LeavePeriodAPI extends Endpoint implements CrudEndpoint
{
    use LeavePeriodServiceTrait;
    use LeaveConfigServiceTrait;
    use NormalizerServiceTrait;
    use DateTimeHelperTrait;

    public const PARAMETER_START_MONTH = 'startMonth';
    public const PARAMETER_START_DAY = 'startDay';
    public const PARAMETER_END_MONTH = 'endMonth';
    public const PARAMETER_END_DAY = 'endDay';

    public const META_PARAMETER_LEAVE_PERIOD_DEFINED = 'leavePeriodDefined';
    public const META_PARAMETER_CURRENT_LEAVE_PERIOD = 'currentLeavePeriod';

    /**
     * @OA\Get(
     *     path="/api/v2/leave/leave-period",
     *     tags={"Leave/Leave Period"},
     *     summary="Get Current Leave Period",
     *     operationId="get-current-leave-period",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Leave-LeavePeriodHistoryModel"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(
     *                     property="currentLeavePeriod",
     *                     ref="#/components/schemas/Leave-LeavePeriodModel"
     *                 ),
     *                 @OA\Property(property="leavePeriodDefined", type="boolean"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $leavePeriodHistory = $this->getLeavePeriodService()->getCurrentLeavePeriodStartDateAndMonth();
        $leavePeriodDefined = $this->getLeaveConfigService()->isLeavePeriodDefined();
        if (!$leavePeriodDefined) {
            $leavePeriodHistory = new LeavePeriodHistory();
            $leavePeriodHistory->setStartMonth(1);
            $leavePeriodHistory->setStartDay(1);
            $leavePeriodHistory->setEndMonth(12);
            $leavePeriodHistory->setEndDay(31);
            $leavePeriodHistory->setCreatedAt($this->getDateTimeHelper()->getNow());
        }
        return new EndpointResourceResult(
            LeavePeriodHistoryModel::class,
            $leavePeriodHistory,
            new ParameterBag(
                [
                    self::META_PARAMETER_LEAVE_PERIOD_DEFINED => $leavePeriodDefined,
                    self::META_PARAMETER_CURRENT_LEAVE_PERIOD => $this->getCurrentLeavePeriod($leavePeriodDefined),
                ]
            )
        );
    }

    /**
     * @param bool $leavePeriodDefined
     * @return array|null
     */
    private function getCurrentLeavePeriod(bool $leavePeriodDefined): ?array
    {
        return $leavePeriodDefined ?
            $this->getNormalizerService()->normalize(
                LeavePeriodModel::class,
                $this->getLeavePeriodService()->getCurrentLeavePeriod(true)
            ) : null;
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection();
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/leave/leave-periods",
     *     tags={"Leave/Leave Period"},
     *     summary="List All Leave Periods",
     *     operationId="list-all-leave-periods",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Leave-LeavePeriodModel"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(
     *                     property="currentLeavePeriod",
     *                     ref="#/components/schemas/Leave-LeavePeriodModel"
     *                 ),
     *                 @OA\Property(property="leavePeriodDefined", type="boolean"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $leavePeriodDefined = $this->getLeaveConfigService()->isLeavePeriodDefined();
        return new EndpointCollectionResult(
            LeavePeriodModel::class,
            $this->getLeavePeriodService()->getGeneratedLeavePeriodList(),
            new ParameterBag(
                [
                    self::META_PARAMETER_LEAVE_PERIOD_DEFINED => $leavePeriodDefined,
                    self::META_PARAMETER_CURRENT_LEAVE_PERIOD => $this->getCurrentLeavePeriod($leavePeriodDefined),
                ]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    /**
     * @OA\Put(
     *     path="/api/v2/leave/leave-period",
     *     tags={"Leave/Leave Period"},
     *     summary="Update Leave Period",
     *     operationId="update-leave-period",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="startDay", type="integer"),
     *             @OA\Property(property="startMonth", type="integer"),
     *             @OA\Property(property="endDay", type="integer"),
     *             @OA\Property(property="endMonth", type="integer")
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Leave-LeavePeriodHistoryModel"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(
     *                     property="currentLeavePeriod",
     *                     ref="#/components/schemas/Leave-LeavePeriodModel"
     *                 ),
     *                 @OA\Property(property="leavePeriodDefined", type="boolean"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $leavePeriodDefined = $this->getLeaveConfigService()->isLeavePeriodDefined();
        $leavePeriodHistory = new LeavePeriodHistory();
        $leavePeriodHistory->setStartMonth(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_START_MONTH)
        );
        $leavePeriodHistory->setStartDay(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_START_DAY)
        );
        $leavePeriodHistory->setEndMonth(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_END_MONTH)
        );
        $leavePeriodHistory->setEndDay(
            $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_END_DAY)
        );
        $leavePeriodHistory->setCreatedAt($this->getDateTimeHelper()->getNow());
        $this->getLeavePeriodService()
            ->getLeavePeriodDao()
            ->saveLeavePeriodHistory($leavePeriodHistory);

        if (!$leavePeriodDefined) {
            /** @var MenuService $menuService */
            $menuService = $this->getContainer()->get(Services::MENU_SERVICE);
            $menuService->enableModuleMenuItems('leave');
        }
        return new EndpointResourceResult(
            LeavePeriodHistoryModel::class,
            $leavePeriodHistory,
            new ParameterBag(
                [
                    self::META_PARAMETER_LEAVE_PERIOD_DEFINED => $leavePeriodDefined,
                    self::META_PARAMETER_CURRENT_LEAVE_PERIOD => $this->getCurrentLeavePeriod($leavePeriodDefined),
                ]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_START_MONTH,
                new Rule(Rules::IN, [$this->getLeavePeriodService()->getMonthNumberList()])
            ),
            new ParamRule(
                self::PARAMETER_START_DAY,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::CALLBACK, [
                    function (int $startDay) {
                        $startMonth = $this->getRequestParams()->getInt(
                            RequestParams::PARAM_TYPE_BODY,
                            self::PARAMETER_START_MONTH
                        );
                        $allowedDaysForMonth = $this->getLeavePeriodService()->getListOfDates($startMonth, false);
                        return in_array($startDay, $allowedDaysForMonth);
                    }
                ])
            ),
            new ParamRule(
                self::PARAMETER_END_MONTH,
                new Rule(Rules::IN, [$this->getLeavePeriodService()->getMonthNumberList()])
            ),
            new ParamRule(
                self::PARAMETER_END_DAY,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::CALLBACK, [
                    function (int $endDay) {
                        $endMonth = $this->getRequestParams()->getInt(
                            RequestParams::PARAM_TYPE_BODY,
                            self::PARAMETER_END_MONTH
                        );
                        $allowedDaysForMonth = $this->getLeavePeriodService()->getListOfDates($endMonth, false);
                        return in_array($endDay, $allowedDaysForMonth);
                    }
                ])
            ),
        );
        $paramRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramRules;
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
