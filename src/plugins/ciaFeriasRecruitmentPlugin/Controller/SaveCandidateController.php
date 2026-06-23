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
use CiaFerias\Core\Traits\Controller\VueComponentPermissionTrait;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Entity\Candidate;
use CiaFerias\Entity\CandidateVacancy;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Recruitment\Service\RecruitmentAttachmentService;
use CiaFerias\Recruitment\Traits\Service\CandidateServiceTrait;

class SaveCandidateController extends AbstractVueController implements CapableViewController
{
    use CandidateServiceTrait;
    use ConfigServiceTrait;
    use UserRoleManagerTrait;
    use VueComponentPermissionTrait;
    use AuthUserTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        if ($request->attributes->has('id')) {
            $id = $request->attributes->getInt('id');

            if (is_null($this->getCandidateService()->getCandidateDao()->getCandidateById($id))) {
                throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
            }
            $component = new Component('view-candidate-profile');
            $candidateVacancy = $this->getCandidateService()->getCandidateDao()->getCandidateVacancyByCandidateId($id);
            $updatable = true;
            if ($candidateVacancy instanceof CandidateVacancy) {
                $rolesToExclude = [];
                $hiringManager = $candidateVacancy->getVacancy()->getHiringManager();
                if ($hiringManager !== null) {
                    $hiringManagerEmpNumber = $candidateVacancy->getVacancy()->getHiringManager()->getEmpNumber();
                    if ($hiringManagerEmpNumber !== $this->getAuthUser()->getEmpNumber()) {
                        $rolesToExclude = ['HiringManager', 'Interviewer'];
                    }
                }
                $updatable = $this->getUserRoleManager()->isEntityAccessible(
                    Candidate::class,
                    $id,
                    null,
                    $rolesToExclude
                );
            }
            $component->addProp(new Prop('updatable', Prop::TYPE_BOOLEAN, $updatable));
            $component->addProp(new Prop('candidate-id', Prop::TYPE_NUMBER, $id));
        } else {
            $component = new Component('save-candidate');
        }

        $component->addProp(
            new Prop('max-file-size', Prop::TYPE_NUMBER, $this->getConfigService()->getMaxAttachmentSize())
        );
        $component->addProp(
            new Prop(
                'allowed-file-types',
                Prop::TYPE_ARRAY,
                RecruitmentAttachmentService::ALLOWED_CANDIDATE_ATTACHMENT_FILE_TYPES
            )
        );
        $this->setComponent($component);
    }

    /**
     * @throws RequestForwardableException
     */
    public function isCapable(Request $request): bool
    {
        if ($request->attributes->has('id')) {
            $id = $request->attributes->getInt('id');

            if (is_null($this->getCandidateService()->getCandidateDao()->getCandidateById($id))) {
                throw new RequestForwardableException(NoRecordsFoundController::class . '::handle');
            }
            if (!$this->getUserRoleManager()->isEntityAccessible(Candidate::class, $id)) {
                return false;
            }
            return true;
        } elseif (!$this->getUserRoleManager()->getDataGroupPermissions(['recruitment_candidates'])->canCreate()) {
            return false;
        } else {
            return true;
        }
    }
}
