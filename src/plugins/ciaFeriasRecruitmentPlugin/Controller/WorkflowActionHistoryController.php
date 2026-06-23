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

namespace CiaFerias\Recruitment\Controller;

use CiaFerias\Core\Authorization\Controller\CapableViewController;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\Common\NoRecordsFoundController;
use CiaFerias\Core\Controller\Exception\RequestForwardableException;
use CiaFerias\Core\Traits\Auth\AuthUserTrait;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\Candidate;
use CiaFerias\Entity\CandidateHistory;
use CiaFerias\Entity\Interview;
use CiaFerias\Entity\Vacancy;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Recruitment\Dto\CandidateActionHistory;
use CiaFerias\Recruitment\Traits\Service\CandidateServiceTrait;

class WorkflowActionHistoryController extends AbstractVueController implements CapableViewController
{
    use UserRoleManagerTrait;
    use CandidateServiceTrait;
    use AuthUserTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('view-action-history');
        $candidateId = $request->attributes->getInt('candidateId');
        $historyId = $request->attributes->getInt('historyId');

        $candidateHistory = $this->getCandidateService()
            ->getCandidateDao()->
            getCandidateHistoryRecordByCandidateIdAndHistoryId($candidateId, $historyId);

        if ($candidateHistory instanceof CandidateHistory && $candidateHistory->getInterview() instanceof Interview) {
            $rolesToExclude = [];
            $hiringManagerEmpNumber = $candidateHistory->getVacancy()->getHiringManager()->getEmpNumber();
            if ($hiringManagerEmpNumber !== $this->getAuthUser()->getEmpNumber()) {
                $rolesToExclude = ['HiringManager', 'Interviewer'];
            }
            $editable = $this->getUserRoleManager()->isEntityAccessible(
                Candidate::class,
                $candidateId,
                null,
                $rolesToExclude
            );
            $component->addProp(new Prop('editable', Prop::TYPE_BOOLEAN, $editable));
        }

        $component->addProp(new Prop('candidate-id', Prop::TYPE_NUMBER, $candidateId));
        $component->addProp(new Prop('history-id', Prop::TYPE_NUMBER, $historyId));
        $this->setComponent($component);
    }

    public function isCapable(
        Request $request
    ): bool {
        if ($request->attributes->has('candidateId') && $request->attributes->has('historyId')) {
            $candidateId = $request->attributes->getInt('candidateId');
            $historyId = $request->attributes->getInt('historyId');

            $candidateHistory = $this->getCandidateService()
                ->getCandidateDao()
                ->getCandidateHistoryRecordByCandidateIdAndHistoryId($candidateId, $historyId);
            if (!$candidateHistory instanceof CandidateHistory) {
                throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
            }
            if (!$this->getUserRoleManager()->isEntityAccessible(Candidate::class, $candidateId)) {
                return false;
            }
            if (!$this->getUserRoleManager()->isEntityAccessible(CandidateHistory::class, $historyId)) {
                return false;
            }
            if ($candidateHistory->getVacancy() instanceof Vacancy) {
                $rolesToExclude = [];
                $hiringManagerEmpNumber = $candidateHistory->getVacancy()->getHiringManager()->getEmpNumber();
                if ($hiringManagerEmpNumber !== $this->getAuthUser()->getEmpNumber()) {
                    $rolesToExclude = ['HiringManager'];
                }
                $accessibleActionHistoryIds = $this->getUserRoleManager()->getAccessibleEntityIds(
                    CandidateActionHistory::class,
                    null,
                    null,
                    $rolesToExclude
                );
                if (!in_array($candidateHistory->getAction(), $accessibleActionHistoryIds)) {
                    return false;
                }
                $currentVacancyId = $this->getCandidateService()
                    ->getCandidateDao()
                    ->getCurrentVacancyIdByCandidateId($candidateId);
                if ($currentVacancyId != $candidateHistory->getVacancy()->getId()) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
