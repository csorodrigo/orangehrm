<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://orangehrm.com
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

namespace OrangeHRM\Tests\Leave\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Leave\Api\VacationPlanningAPI;
use OrangeHRM\Tests\Util\EndpointTestCase;

/**
 * @group Leave
 * @group APIv2
 */
class VacationPlanningAPITest extends EndpointTestCase
{
    public function testGetValidationRuleForGetAll(): void
    {
        $api = new VacationPlanningAPI($this->getRequest());
        $rules = $api->getValidationRuleForGetAll();

        $this->assertTrue(
            $this->validate(
                [
                    CommonParams::PARAMETER_LIMIT => 0,
                    VacationPlanningAPI::FILTER_SUBUNIT_ID => 0,
                ],
                $rules
            )
        );
        $this->assertTrue(
            $this->validate(
                [
                    CommonParams::PARAMETER_LIMIT => 0,
                    'legacy_extra_param' => 'ignore-me',
                ],
                $rules
            )
        );
        $this->assertTrue(
            $this->validate(
                [
                    CommonParams::PARAMETER_LIMIT => 0,
                    'sortField' => 'share.createdAtUtc',
                ],
                $rules
            )
        );
    }
}
