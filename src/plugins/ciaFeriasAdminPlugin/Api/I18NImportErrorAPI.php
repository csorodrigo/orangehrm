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

use CiaFerias\Admin\Api\Model\I18NImportErrorModel;
use CiaFerias\Admin\Dto\I18NImportErrorSearchFilterParams;
use CiaFerias\Admin\Traits\Service\LocalizationServiceTrait;
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
use CiaFerias\Entity\I18NLanguage;

class I18NImportErrorAPI extends Endpoint implements CollectionEndpoint
{
    use AuthUserTrait;
    use LocalizationServiceTrait;

    public const PARAMETER_LANGUAGE_ID = 'languageId';

    /**
     * @OA\Get(
     *     path="/api/v2/admin/i18n/languages/{languageId}/translations/errors",
     *     tags={"Admin/I18N"},
     *     summary="List I18N Import Errors",
     *     operationId="list-i18n-import-errors",
     *     @OA\PathParameter(
     *         name="languageId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=I18NImportErrorSearchFilterParams::ALLOWED_SORT_FIELDS)
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
     *                 @OA\Items(ref="#/components/schemas/Admin-I18NImportErrorModel")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $importErrorSearchFilterParams = new I18NImportErrorSearchFilterParams();
        $this->setSortingAndPaginationParams($importErrorSearchFilterParams);

        $importErrorSearchFilterParams->setLanguageId(
            $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_LANGUAGE_ID
            )
        );
        $importErrorSearchFilterParams->setEmpNumber(
            $this->getAuthUser()->getEmpNumber()
        );

        $items = $this->getLocalizationService()->getLocalizationDao()->getImportErrorList($importErrorSearchFilterParams);
        $count = $this->getLocalizationService()->getLocalizationDao()->getImportErrorCount($importErrorSearchFilterParams);

        return new EndpointCollectionResult(
            I18NImportErrorModel::class,
            $items,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_LANGUAGE_ID,
                new Rule(Rules::POSITIVE),
                new Rule(Rules::ENTITY_ID_EXISTS, [I18NLanguage::class])
            ),
            ...$this->getSortingAndPaginationParamsRules(I18NImportErrorSearchFilterParams::ALLOWED_SORT_FIELDS)
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
