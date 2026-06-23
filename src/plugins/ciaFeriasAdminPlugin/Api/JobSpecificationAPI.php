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

namespace CiaFerias\Admin\Api;

use CiaFerias\Admin\Api\Model\JobSpecificationModel;
use CiaFerias\Admin\Dto\PartialJobSpecificationAttachment;
use CiaFerias\Admin\Service\JobTitleService;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\ResourceEndpoint;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;

class JobSpecificationAPI extends Endpoint implements ResourceEndpoint
{
    public const PARAMETER_JOB_TITLE_ID = 'jobTitleId';

    /**
     * @var null|JobTitleService
     */
    protected ?JobTitleService $jobTitleService = null;

    /**
     * @return JobTitleService
     */
    public function getJobTitleService(): JobTitleService
    {
        if (!$this->jobTitleService instanceof JobTitleService) {
            $this->jobTitleService = new JobTitleService();
        }
        return $this->jobTitleService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/admin/job-titles/{id}/specification",
     *     tags={"Admin/Job Specification"},
     *     summary="Get Job Specification",
     *     operationId="get-job-specification",
     *     @OA\PathParameter(
     *         name="id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Admin-JobSpecificationModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResourceResult
    {
        $jobTitleId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_JOB_TITLE_ID
        );
        $partialJobSpecificationAttachment = $this->getJobTitleService()->getJobTitleDao()->getJobSpecificationByJobTitleId($jobTitleId);
        $this->throwRecordNotFoundExceptionIfNotExist($partialJobSpecificationAttachment, PartialJobSpecificationAttachment::class);

        return new EndpointResourceResult(
            JobSpecificationModel::class,
            $partialJobSpecificationAttachment,
            new ParameterBag([self::PARAMETER_JOB_TITLE_ID => $jobTitleId])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_JOB_TITLE_ID,
                new Rule(Rules::POSITIVE)
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
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
