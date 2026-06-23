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

namespace CiaFerias\Buzz\Api;

use Exception;
use OpenApi\Annotations as OA;
use CiaFerias\Buzz\Api\Model\BuzzLikeOnShareModel;
use CiaFerias\Buzz\Dto\BuzzLikeOnShareSearchFilterParams;
use CiaFerias\Buzz\Traits\Service\BuzzServiceTrait;
use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\CollectionEndpoint;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointCollectionResult;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\Exception\BadRequestException;
use CiaFerias\Core\Api\V2\Exception\InvalidParamException;
use CiaFerias\Core\Api\V2\Model\ArrayModel;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\ORM\EntityManagerHelperTrait;
use CiaFerias\Entity\BuzzLikeOnShare;
use CiaFerias\Entity\BuzzShare;
use CiaFerias\ORM\Exception\TransactionException;

class BuzzLikeOnShareAPI extends Endpoint implements CollectionEndpoint
{
    use AuthUserTrait;
    use BuzzServiceTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_SHARE_ID = 'shareId';

    /**
     * @OA\Get(
     *     path="/api/v2/buzz/shares/{shareId}/likes",
     *     tags={"Buzz/Share Likes"},
     *     summary="List Likes on a Share/Post",
     *     operationId="list-likes-on-a-share-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=BuzzLikeOnShareSearchFilterParams::ALLOWED_SORT_FIELDS)
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
     *                 @OA\Items(ref="#/components/schemas/Buzz-BuzzLikeOnShareModel")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $shareId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SHARE_ID
        );
        $buzzShare = $this->getBuzzService()->getBuzzDao()->getBuzzShareById($shareId);
        if (!$buzzShare instanceof BuzzShare) {
            throw $this->getInvalidParamException(self::PARAMETER_SHARE_ID);
        }

        $buzzLikeOnShareSearchFilterParams = new BuzzLikeOnShareSearchFilterParams();
        $buzzLikeOnShareSearchFilterParams->setShareId($shareId);

        $this->setSortingAndPaginationParams($buzzLikeOnShareSearchFilterParams);

        $likes = $this->getBuzzService()->getBuzzLikeDao()->getBuzzLikeOnShareList($buzzLikeOnShareSearchFilterParams);
        $likeCount = $this->getBuzzService()
            ->getBuzzLikeDao()
            ->getBuzzLikeOnShareCount($buzzLikeOnShareSearchFilterParams);

        return new EndpointCollectionResult(
            BuzzLikeOnShareModel::class,
            $likes,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $likeCount])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SHARE_ID,
                new Rule(Rules::POSITIVE),
            ),
            ...$this->getSortingAndPaginationParamsRules(BuzzLikeOnShareSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/buzz/shares/{shareId}/likes",
     *     tags={"Buzz/Share Likes"},
     *     summary="Like a Share/Post",
     *     operationId="like-a-share-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Buzz-BuzzLikeOnShareModel"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request - Liking a post that is already liked",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", default="400"),
     *                 @OA\Property(property="message", type="string", default="Already liked")
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $shareId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_SHARE_ID
            );

            $buzzShare = $this->getBuzzService()->getBuzzDao()->getBuzzShareById($shareId);
            if (!$buzzShare instanceof BuzzShare) {
                throw $this->getInvalidParamException(self::PARAMETER_SHARE_ID);
            }

            $buzzShareOnLike = $this->getBuzzService()
                ->getBuzzLikeDao()
                ->getBuzzLikeOnShareByShareIdAndEmpNumber($shareId, $this->getAuthUser()->getEmpNumber());
            if ($buzzShareOnLike instanceof BuzzLikeOnShare) {
                throw $this->getBadRequestException('Already liked');
            }

            $buzzShare->getDecorator()->increaseNumOfLikesByOne();
            $this->getBuzzService()->getBuzzDao()->saveBuzzShare($buzzShare);

            $like = new BuzzLikeOnShare();
            $this->setBuzzLikeOnShare($like);

            $like = $this->getBuzzService()->getBuzzLikeDao()->saveBuzzLikeOnShare($like);
            $this->commitTransaction();

            return new EndpointResourceResult(BuzzLikeOnShareModel::class, $like);
        } catch (InvalidParamException | BadRequestException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @param BuzzLikeOnShare $buzzLikeOnShare
     */
    private function setBuzzLikeOnShare(BuzzLikeOnShare $buzzLikeOnShare): void
    {
        $buzzLikeOnShare->getDecorator()->setShareByShareId(
            $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_SHARE_ID
            )
        );

        $buzzLikeOnShare->getDecorator()->setEmployeeByEmpNumber(
            $this->getAuthUser()->getEmpNumber()
        );

        $buzzLikeOnShare->setLikedAtUtc();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SHARE_ID,
                new Rule(Rules::POSITIVE),
            ),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/buzz/shares/{shareId}/likes",
     *     tags={"Buzz/Share Likes"},
     *     summary="Unlike a Liked Share/Post",
     *     operationId="unlike-a-liked-share-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="shareId", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request - Unlike a post that is not liked",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", default="400"),
     *                 @OA\Property(property="message", type="string", default="Not previously liked")
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $shareId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_SHARE_ID
            );

            $buzzShare = $this->getBuzzService()->getBuzzDao()->getBuzzShareById($shareId);
            if (!$buzzShare instanceof BuzzShare) {
                throw $this->getInvalidParamException(self::PARAMETER_SHARE_ID);
            }

            $buzzShareOnLike = $this->getBuzzService()
                ->getBuzzLikeDao()
                ->getBuzzLikeOnShareByShareIdAndEmpNumber($shareId, $this->getAuthUser()->getEmpNumber());
            if (!$buzzShareOnLike instanceof BuzzLikeOnShare) {
                throw $this->getBadRequestException('Not previously liked');
            }

            $buzzShare->getDecorator()->decreaseNumOfLikesByOne();
            $this->getBuzzService()->getBuzzDao()->saveBuzzShare($buzzShare);

            $this->getBuzzService()->getBuzzLikeDao()
                ->deleteBuzzLikeOnShare($shareId, $this->getAuthUser()->getEmpNumber());
            $this->commitTransaction();

            return new EndpointResourceResult(ArrayModel::class, [self::PARAMETER_SHARE_ID => $shareId]);
        } catch (InvalidParamException | BadRequestException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SHARE_ID,
                new Rule(Rules::POSITIVE),
            ),
        );
    }
}
