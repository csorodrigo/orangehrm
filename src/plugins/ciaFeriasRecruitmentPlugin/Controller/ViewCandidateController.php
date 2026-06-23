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

use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\Controller\VueComponentPermissionTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Recruitment\Service\CandidateService;
use CiaFerias\Recruitment\Traits\Service\CandidateServiceTrait;

class ViewCandidateController extends AbstractVueController
{
    use VueComponentPermissionTrait;
    use CandidateServiceTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $component = new Component('view-candidates-list');

        if ($request->query->has('statusId')) {
            $statusId = $request->query->getInt('statusId');
            $candidateStatus = array_map(function ($key, $value) {
                return [
                    'id' => $key,
                    'label' => $value,
                ];
            }, array_keys(CandidateService::STATUS_MAP), CandidateService::STATUS_MAP);

            $component->addProp(
                new Prop(
                    'status',
                    Prop::TYPE_OBJECT,
                    $candidateStatus[$statusId - 1],
                )
            );
        }

        $this->setComponent($component);
        $this->setPermissions(['recruitment_candidates']);
    }
}
