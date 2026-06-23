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

use CiaFerias\Admin\Traits\Service\LocalizationServiceTrait;
use CiaFerias\Core\Controller\AbstractFileController;
use CiaFerias\Core\Traits\Service\TextHelperTrait;
use CiaFerias\Entity\I18NLanguage;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Http\Response;

class LanguagePackage extends AbstractFileController
{
    use TextHelperTrait;
    use LocalizationServiceTrait;

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $response = $this->getResponse();

        if ($request->attributes->get('languageId')) {
            $languageId = $request->attributes->getInt('languageId');
            $language = $this->getLocalizationService()->getLocalizationDao()
                ->getLanguageById($languageId);

            if (!($language instanceof I18NLanguage)
                || !($language->isAdded()
                    && $language->isEnabled())
            ) {
                return $this->handleBadRequest($response);
            }

            $xliffContent = $this->getLocalizationService()
                ->exportLanguagePackage($language);

            $fileName = sprintf('i18n-%s.xlf', $language->getCode());

            $this->setCommonHeadersToResponse(
                $fileName,
                'application/xliff+xml',
                $this->getTextHelper()->strLength($xliffContent, '8bit'),
                $response
            );
            $response->setContent($xliffContent);
            return $response;
        }
        return $this->handleBadRequest();
    }
}
