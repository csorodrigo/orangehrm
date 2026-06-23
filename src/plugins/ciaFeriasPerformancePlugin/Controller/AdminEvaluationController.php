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

namespace CiaFerias\Performance\Controller;

use CiaFerias\Core\Authorization\Controller\CapableViewController;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\Common\NoRecordsFoundController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\PerformanceReview;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Performance\Traits\Service\PerformanceReviewServiceTrait;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class AdminEvaluationController extends AbstractVueController implements CapableViewController
{
    use PerformanceReviewServiceTrait;
    use UserRoleManagerTrait;
    use EmployeeServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        if ($request->attributes->has('id')) {
            $id = $request->attributes->getInt('id');
            $component = new Component('admin-evaluation');
            $review = $this->getPerformanceReviewService()->getPerformanceReviewDao()->getPerformanceReviewById($id);
            if (!is_null($review)) {
                $this->setReviewProps($component, $review);
                if ($this->isUserPerformanceReviewEvaluator($id)) {
                    $component->addProp(new Prop('is-reviewer', Prop::TYPE_BOOLEAN, true));
                }
            }
            $this->setComponent($component);
        } else {
            throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
        }
    }

    /**
     * @inheritDoc
     */
    public function isCapable(Request $request): bool
    {
        $id = $request->attributes->getInt('id');
        $performanceReview = $this->getPerformanceReviewService()
            ->getPerformanceReviewDao()
            ->getPerformanceReviewById($id);
        if (is_null($performanceReview)
            || ($performanceReview->getEmployee() instanceof Employee
                && !is_null($performanceReview->getEmployee()->getPurgedAt()))) {
            throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
        }
        return $this->getUserRoleManager()->isEntityAccessible(PerformanceReview::class, $id, null, ['ESS']);
    }

    /**
     * @param Component $component
     * @param PerformanceReview $performanceReview
     */
    protected function setReviewProps(Component $component, PerformanceReview $performanceReview): void
    {
        $component->addProp(new Prop('review-id', Prop::TYPE_NUMBER, $performanceReview->getId()));
        $component->addProp(new Prop('status', Prop::TYPE_NUMBER, $performanceReview->getStatusId()));
        $component->addProp(new Prop('review-period-start', Prop::TYPE_STRING, $performanceReview->getDecorator()->getReviewPeriodStart()));
        $component->addProp(new Prop('review-period-end', Prop::TYPE_STRING, $performanceReview->getDecorator()->getReviewPeriodEnd()));
        $component->addProp(new Prop('due-date', Prop::TYPE_STRING, $performanceReview->getDecorator()->getDueDate()));
    }

    /**
     * @param int $performanceReviewId
     * @return bool
     */
    private function isUserPerformanceReviewEvaluator(int $performanceReviewId): bool
    {
        return $this->getUserRoleManager()->isEntityAccessible(
            PerformanceReview::class,
            $performanceReviewId,
            null,
            ['Admin', 'ESS'],
            ['Supervisor']
        );
    }
}
