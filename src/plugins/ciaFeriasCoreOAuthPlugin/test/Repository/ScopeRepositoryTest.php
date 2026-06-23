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

namespace CiaFerias\Tests\OAuth\Repository;

use CiaFerias\OAuth\Dto\Entity\ClientEntity;
use CiaFerias\OAuth\Repository\ScopeRepository;
use CiaFerias\Tests\Util\KernelTestCase;

/**
 * @group OAuth
 * @group Dao
 */
class ScopeRepositoryTest extends KernelTestCase
{
    public function testGetScopeEntityByIdentifier(): void
    {
        $scopeRepository = new ScopeRepository();
        $this->assertNull($scopeRepository->getScopeEntityByIdentifier('profile'));
    }

    public function testFinalizeScopes(): void
    {
        $scopeRepository = new ScopeRepository();
        $clientEntity = new ClientEntity(
            1,
            'cia_ferias_mobile_app',
            'com.ciaferias.opensource://oauthredirect',
            false,
            'Mobile App'
        );
        $this->assertEmpty($scopeRepository->finalizeScopes([], 'authorization_code', $clientEntity, null));
    }
}
