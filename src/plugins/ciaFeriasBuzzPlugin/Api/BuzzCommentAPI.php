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
use CiaFerias\Buzz\Api\Model\BuzzCommentModel;
use CiaFerias\Buzz\Api\Model\BuzzDetailedCommentModel;
use CiaFerias\Buzz\Dto\BuzzCommentSearchFilterParams;
use CiaFerias\Buzz\Traits\Service\BuzzServiceTrait;
use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\CrudEndpoint;
use CiaFerias\Core\Api\V2\Endpoint;
use CiaFerias\Core\Api\V2\EndpointCollectionResult;
use CiaFerias\Core\Api\V2\EndpointResourceResult;
use CiaFerias\Core\Api\V2\EndpointResult;
use CiaFerias\Core\Api\V2\Exception\ForbiddenException;
use CiaFerias\Core\Api\V2\Exception\InvalidParamException;
use CiaFerias\Core\Api\V2\Exception\RecordNotFoundException;
use CiaFerias\Core\Api\V2\Model\ArrayModel;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Api\V2\RequestParams;
use CiaFerias\Core\Api\V2\Validator\ParamRule;
use CiaFerias\Core\Api\V2\Validator\ParamRuleCollection;
use CiaFerias\Core\Api\V2\Validator\Rule;
use CiaFerias\Core\Api\V2\Validator\Rules;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\ORM\EntityManagerHelperTrait;
use CiaFerias\Entity\BuzzComment;
use CiaFerias\Entity\BuzzShare;
use CiaFerias\ORM\Exception\TransactionException;

class BuzzCommentAPI extends Endpoint implements CrudEndpoint
{
    use BuzzServiceTrait;
    use AuthUserTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_TEXT = 'text';
    public const PARAMETER_SHARE_ID = 'shareId';
    public const PARAMETER_COMMENT_ID = 'commentId';
    public const PARAMETER_MODEL = 'model';

    public const MODEL_DEFAULT = 'default';
    public const MODEL_DETAILED = 'detailed';
    public const MODEL_MAP = [
        self::MODEL_DEFAULT => BuzzCommentModel::class,
        self::MODEL_DETAILED => BuzzDetailedCommentModel::class,
    ];

    /**
     * @OA\Get(
     *     path="/api/v2/buzz/shares/{shareId}/comments/{commentId}",
     *     tags={"Buzz/Comments"},
     *     summary="Get a Comment on a Post",
     *     operationId="get-a-comment-on-a-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\PathParameter(
     *         name="commentId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT, CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DETAILED},
     *             default=CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 oneOf={
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzCommentModel"),
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzDetailedCommentModel"),
     *                 }
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $commentId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_COMMENT_ID);
        $shareId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SHARE_ID);
        $buzzComment = $this->getBuzzService()->getBuzzDao()->getBuzzCommentById($commentId, $shareId);
        $this->throwRecordNotFoundExceptionIfNotExist($buzzComment, BuzzComment::class);

        return new EndpointResourceResult($this->getModelClass(), $buzzComment);
    }

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        $model = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_MODEL,
            self::MODEL_DEFAULT
        );
        return self::MODEL_MAP[$model];
    }

    protected function getModelParamRule(): ParamRule
    {
        return $this->getValidationDecorator()->notRequiredParamRule(
            new ParamRule(
                self::PARAMETER_MODEL,
                new Rule(Rules::IN, [array_keys(self::MODEL_MAP)])
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_COMMENT_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_SHARE_ID, new Rule(Rules::POSITIVE)),
            $this->getModelParamRule()
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/buzz/shares/{shareId}/comments",
     *     tags={"Buzz/Comments"},
     *     summary="List All Comments on a Post",
     *     operationId="list-all-comments-on-a-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT, CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DETAILED},
     *             default=CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sortField",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum=BuzzCommentSearchFilterParams::ALLOWED_SORT_FIELDS)
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
     *                 @OA\Items(oneOf={
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzCommentModel"),
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzDetailedCommentModel"),
     *                 })
     *             ),
     *             @OA\Property(property="meta",
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
        $shareId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SHARE_ID);
        $filterParams = new BuzzCommentSearchFilterParams();
        $filterParams->setShareId($shareId);
        $this->setSortingAndPaginationParams($filterParams);

        $comments = $this->getBuzzService()->getBuzzDao()->getBuzzComments($filterParams);
        $count = $this->getBuzzService()->getBuzzDao()->getBuzzCommentsCount($filterParams);

        $modelClass = $this->getModelClass();

        return new EndpointCollectionResult(
            $modelClass,
            $comments,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SHARE_ID, new Rule(Rules::POSITIVE)),
            $this->getModelParamRule(),
            ...$this->getSortingAndPaginationParamsRules(BuzzCommentSearchFilterParams::ALLOWED_SORT_FIELDS),
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/buzz/shares/{shareId}/comments",
     *     tags={"Buzz/Comments"},
     *     summary="Comment on a Post",
     *     operationId="comment-on-a-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT, CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DETAILED},
     *             default=CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="text", type="string"),
     *             required={"text"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 oneOf={
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzCommentModel"),
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzDetailedCommentModel"),
     *                 }
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $shareId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SHARE_ID);
            $buzzShare = $this->getBuzzService()->getBuzzDao()->getBuzzShareById($shareId);
            if (!$buzzShare instanceof BuzzShare) {
                throw $this->getInvalidParamException(self::PARAMETER_SHARE_ID);
            }
            $comment = new BuzzComment();
            $this->setBuzzCommentText($comment);
            $comment->getDecorator()->setShareById($shareId);
            $comment->getDecorator()->setEmployeeByEmpNumber($this->getAuthUser()->getEmpNumber());
            $comment->setCreatedAtUtc();
            $this->getBuzzService()->getBuzzDao()->saveBuzzComment($comment);

            $comment->getShare()->getDecorator()->increaseNumOfCommentsByOne();
            $this->getBuzzService()->getBuzzDao()->saveBuzzShare($comment->getShare());
            $this->commitTransaction();

            return new EndpointResourceResult($this->getModelClass(), $comment);
        } catch (InvalidParamException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @param BuzzComment $comment
     */
    private function setBuzzCommentText(BuzzComment $comment): void
    {
        $text = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TEXT);
        $comment->setText($text);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SHARE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_TEXT,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::STR_LENGTH, [null, BuzzPostAPI::PARAM_RULE_TEXT_MAX_LENGTH])
            ),
            $this->getModelParamRule()
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/buzz/shares/{shareId}/comments/{commentId}",
     *     tags={"Buzz/Comments"},
     *     summary="Edit a Comment on a Post",
     *     operationId="edit-a-comment-on-a-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\PathParameter(
     *         name="commentId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="model",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT, CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DETAILED},
     *             default=CiaFerias\Buzz\Api\BuzzCommentAPI::MODEL_DEFAULT
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="text", type="string"),
     *             required={"text"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 oneOf={
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzCommentModel"),
     *                     @OA\Schema(ref="#/components/schemas/Buzz-BuzzDetailedCommentModel"),
     *                 }
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     *
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $commentId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_COMMENT_ID);
        $shareId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SHARE_ID);
        $buzzComment = $this->getBuzzService()->getBuzzDao()->getBuzzCommentById($commentId, $shareId);
        $this->throwRecordNotFoundExceptionIfNotExist($buzzComment, BuzzComment::class);

        if (!$this->getBuzzService()->canUpdateBuzzComment($buzzComment->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }

        $this->setBuzzCommentText($buzzComment);
        $buzzComment->setUpdatedAtUtc();
        $this->getBuzzService()->getBuzzDao()->saveBuzzComment($buzzComment);

        return new EndpointResourceResult($this->getModelClass(), $buzzComment);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_COMMENT_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_SHARE_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(
                self::PARAMETER_TEXT,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, BuzzPostAPI::PARAM_RULE_TEXT_MAX_LENGTH])
            ),
            $this->getModelParamRule()
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/buzz/shares/{shareId}/comments/{commentId}",
     *     tags={"Buzz/Comments"},
     *     summary="Delete a Comment on a Post",
     *     operationId="delete-a-comment-on-a-post",
     *     @OA\PathParameter(
     *         name="shareId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\PathParameter(
     *         name="commentId",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="commentId", type="integer"),
     *                 @OA\Property(property="shareId", type="integer"),
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound"),
     *     @OA\Response(response="403", ref="#/components/responses/ForbiddenResponse")
     * )
     *
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $this->beginTransaction();
        try {
            $commentId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_ATTRIBUTE,
                self::PARAMETER_COMMENT_ID
            );
            $shareId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SHARE_ID);
            $buzzComment = $this->getBuzzService()->getBuzzDao()->getBuzzCommentById($commentId, $shareId);
            $this->throwRecordNotFoundExceptionIfNotExist($buzzComment, BuzzComment::class);

            if (!$this->getBuzzService()->canDeleteBuzzComment($buzzComment->getEmployee()->getEmpNumber())) {
                throw $this->getForbiddenException();
            }

            $comment = clone $buzzComment;
            $this->getBuzzService()->getBuzzDao()->deleteBuzzComment($buzzComment);

            $comment->getShare()->getDecorator()->decreaseNumOfCommentsByOne();
            $this->getBuzzService()->getBuzzDao()->saveBuzzShare($comment->getShare());
            $this->commitTransaction();

            return new EndpointResourceResult(ArrayModel::class, [
                self::PARAMETER_COMMENT_ID => $commentId,
                self::PARAMETER_SHARE_ID => $shareId
            ]);
        } catch (RecordNotFoundException | ForbiddenException $e) {
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
            new ParamRule(self::PARAMETER_COMMENT_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_SHARE_ID, new Rule(Rules::POSITIVE))
        );
    }
}
