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

namespace CiaFerias\Admin\Controller;

use CiaFerias\Admin\Service\CountryService;
use CiaFerias\Core\Controller\AbstractVueController;
use CiaFerias\Core\Traits\ServiceContainerTrait;
use CiaFerias\Core\Vue\Component;
use CiaFerias\Core\Vue\Prop;
use CiaFerias\Framework\Http\Request;
use CiaFerias\Framework\Services;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class ViewOrganizationGeneralInformationController extends AbstractVueController
{
    use EmployeeServiceTrait;
    use ServiceContainerTrait;

    /**
     * @inheritDoc
     */
    public function preRender(Request $request): void
    {
        $noOfEmployees = $this->getEmployeeService()->getNumberOfEmployees();
        /** @var CountryService $countryService */
        $countryService = $this->getContainer()->get(Services::COUNTRY_SERVICE);
        $countryList = $countryService->getCountryArray();
        $component = new Component('organization-general-information-view');
        $component->addProp(new Prop('number-of-employees', Prop::TYPE_NUMBER, $noOfEmployees));
        $component->addProp(new Prop('country-list', Prop::TYPE_ARRAY, $countryList));
        $this->setComponent($component);
    }
}
