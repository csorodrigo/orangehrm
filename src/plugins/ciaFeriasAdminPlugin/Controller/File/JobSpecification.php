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

namespace CiaFerias\Admin\Controller\File;

use CiaFerias\Admin\Service\JobTitleService;
use CiaFerias\Authentication\Exception\ForbiddenException;
use CiaFerias\Core\Controller\AbstractFileController;
use CiaFerias\Core\Traits\UserRoleManagerTrait;
use CiaFerias\Entity\JobSpecificationAttachment;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;

class JobSpecification extends AbstractFileController
{
    use UserRoleManagerTrait;

    /**
     * @var JobTitleService|null
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
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $attachId = $request->attributes->get('attachId');
        $response = $this->getResponse();

        if ($attachId) {
            $attachment = $this->getJobTitleService()->getJobSpecAttachmentById($attachId);
            if ($attachment instanceof JobSpecificationAttachment) {
                if (!$this->getUserRoleManager()->isEntityAccessible(JobSpecificationAttachment::class, $attachId)) {
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
