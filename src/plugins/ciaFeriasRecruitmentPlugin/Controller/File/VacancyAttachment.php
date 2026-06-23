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

namespace CiaFerias\Recruitment\Controller\File;

use CiaFerias\Authentication\Exception\ForbiddenException;
use CiaFerias\Core\Controller\AbstractFileController;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Entity\VacancyAttachment as VacancyAttachmentEntity;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Recruitment\Traits\Service\RecruitmentAttachmentServiceTrait;

class VacancyAttachment extends AbstractFileController
{
    use RecruitmentAttachmentServiceTrait;
    use UserRoleManagerTrait;

    public function handle(Request $request): Response
    {
        $attachId = $request->attributes->get('attachId');
        $response = $this->getResponse();

        if ($attachId) {
            $attachment = $this->getRecruitmentAttachmentService()
                ->getRecruitmentAttachmentDao()
                ->getVacancyAttachmentById($attachId);
            if ($attachment instanceof VacancyAttachmentEntity) {
                if (!$this->getUserRoleManager()->isEntityAccessible(VacancyAttachmentEntity::class, $attachId)) {
                    throw new ForbiddenException();
                }
                $this->setCommonHeadersToResponse(
                    $attachment->getFileName(),
                    $attachment->getFileType(),
                    $attachment->getFileSize(),
                    $response
                );
                $response->setContent($attachment->getDecorator()->getFileContent());
                return $response;
            }
        }
        return $this->handleBadRequest();
    }
}
