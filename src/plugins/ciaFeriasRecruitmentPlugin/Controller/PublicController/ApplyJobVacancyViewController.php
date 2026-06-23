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

namespace CiaFerias\Recruitment\Controller\PublicController;

use CiaFerias\Authentication\Traits\CsrfTokenManagerTrait;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Controller\PublicControllerInterface;
use CiaFerias\Core\Traits\Service\ConfigServiceTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\CorporateBranding\Traits\ThemeServiceTrait;
use CiaFerias\Entity\Vacancy;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;
use CiaFerias\Recruitment\Service\RecruitmentAttachmentService;
use CiaFerias\Recruitment\Traits\Service\VacancyServiceTrait;

class ApplyJobVacancyViewController extends AbstractVueController implements PublicControllerInterface
{
    use ThemeServiceTrait;
    use ConfigServiceTrait;
    use CsrfTokenManagerTrait;
    use VacancyServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $id = $request->attributes->getInt('id');
        $vacancy = $this->getVacancyService()
            ->getVacancyDao()
            ->getVacancyById($id);
        if (!$vacancy instanceof Vacancy || !$vacancy->getDecorator()->isActiveAndPublished()) {
            $this->setResponse($this->handleBadRequest());
            return;
        }

        $component = new Component('apply-job-vacancy');
        $component->addProp(new Prop('vacancy-id', Prop::TYPE_NUMBER, $id));
        $component->addProp(new Prop('success', Prop::TYPE_BOOLEAN, $request->query->getBoolean('success', false)));
        $component->addProp(
            new Prop('banner-src', Prop::TYPE_STRING, $this->getThemeService()->getClientBannerURL($request))
        );
        $component->addProp(
            new Prop(
                'allowed-file-types',
                Prop::TYPE_ARRAY,
                RecruitmentAttachmentService::ALLOWED_CANDIDATE_ATTACHMENT_FILE_TYPES
            )
        );
        $component->addProp(
            new Prop(
                'token',
                Prop::TYPE_STRING,
                $this->getCsrfTokenManager()->getToken('recruitment-applicant')->getValue()
            )
        );
        $component->addProp(
            new Prop('max-file-size', Prop::TYPE_NUMBER, $this->getConfigService()->getMaxAttachmentSize())
        );
        $this->setComponent($component);
        $this->setTemplate('no_header.html.twig');
    }

    /**
     * @inheritDoc
     */
    protected function handleBadRequest(?Response $response = null): Response
    {
        return ($response ?? $this->getResponse())->setStatusCode(Response::HTTP_BAD_REQUEST);
    }
}
