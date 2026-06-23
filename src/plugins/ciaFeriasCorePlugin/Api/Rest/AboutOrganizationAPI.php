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

namespace CiaFerias\Core\Api\Rest;

use CiaFerias\Admin\Dto\AboutOrganization;
use CiaFerias\Admin\Service\OrganizationService;
use CiaFerias\Config\Config;
use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\Rest\Model\AboutOrganizationModel;
use CiaFerias\Core\Api\V2\ResourceEndpoint;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Entity\Organization;
use CiaFerias\Pim\Dto\EmployeeSearchFilterParams;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class AboutOrganizationAPI extends Endpoint implements ResourceEndpoint
{
    use EmployeeServiceTrait;

    /**
     * @var null|OrganizationService
     */
    protected ?OrganizationService $organizationService = null;

    /**
     * @return OrganizationService
     */
    public function getOrganizationService(): OrganizationService
    {
        if (is_null($this->organizationService)) {
            $this->organizationService = new OrganizationService();
        }
        return $this->organizationService;
    }

    /**
     * @OA\Get(
     *     path="/api/v2/core/about",
     *     tags={"Core/About Organization"},
     *     summary="Get Basic Organization Details",
     *     operationId="get-basic-organization-details",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Core-AboutOrganizationModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $aboutOrganization = new AboutOrganization();
        $employeeParamHolder = new EmployeeSearchFilterParams();
        $employeeParamHolder->setIncludeEmployees("3");
        $organization = $this->getOrganizationService()->getOrganizationGeneralInformation();
        $organizationName = $organization instanceof Organization ? $organization->getName() : 'cia-ferias';
        $numberOfActiveEmployees = $this->getEmployeeService()->getNumberOfEmployees();
        $numberOfPastEmployees = $this->getEmployeeService()->getEmployeeCount($employeeParamHolder);
        $aboutOrganization->setCompanyName($organizationName);
        $aboutOrganization->setVersion(Config::PRODUCT_VERSION);
        $aboutOrganization->setNumberOfActiveEmployee($numberOfActiveEmployees);
        $aboutOrganization->setNumberOfPastEmployee($numberOfPastEmployees);
        return new EndpointResourceResult(AboutOrganizationModel::class, $aboutOrganization);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
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
