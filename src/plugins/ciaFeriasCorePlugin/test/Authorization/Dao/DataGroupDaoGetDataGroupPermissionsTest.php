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

namespace CiaFerias\Tests\Core\Authorization\Dao;

use CiaFerias\Config\Config;
use CiaFerias\Core\Authorization\Dao\DataGroupDao;
use CiaFerias\Core\Authorization\Dto\DataGroupPermissionFilterParams;
use CiaFerias\Entity\UserRole;
use CiaFerias\Tests\Util\TestCase;
use CiaFerias\Tests\Util\TestDataService;

/**
 * @group Core
 * @group Dao
 */
class DataGroupDaoGetDataGroupPermissionsTest extends TestCase
{
    /**
     * @var DataGroupDao
     */
    private DataGroupDao $dao;

    protected function setUp(): void
    {
        $this->fixture = Config::get(Config::PLUGINS_DIR)
            . '/ciaFeriasCorePlugin/test/fixtures/DataGroupDaoGetDataGroupPermissionsTest.yml';
        TestDataService::populate($this->fixture);

        $this->dao = new DataGroupDao();
    }

    public function testGetDataGroupPermissionsWithEmptyParams(): void
    {
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(0, $permissions);
    }

    public function testGetDataGroupPermissionsWithAdmin(): void
    {
        $adminUserRole = $this->getEntityReference(UserRole::class, 1);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$adminUserRole]);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions[0]->canRead());
        $this->assertTrue($permissions[0]->canCreate());
        $this->assertFalse($permissions[0]->canUpdate());
        $this->assertFalse($permissions[0]->canDelete());
        $this->assertFalse($permissions[0]->isSelf());
    }

    public function testGetDataGroupPermissionsWithEss(): void
    {
        $essUserRole = $this->getEntityReference(UserRole::class, 2);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$essUserRole]);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions[0]->canRead());
        $this->assertFalse($permissions[0]->canCreate());
        $this->assertFalse($permissions[0]->canUpdate());
        $this->assertFalse($permissions[0]->canDelete());
        $this->assertFalse($permissions[0]->isSelf());
    }

    public function testGetDataGroupPermissionsWithoutApi(): void
    {
        $supervisorUserRole = $this->getEntityReference(UserRole::class, 3);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$supervisorUserRole]);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions[0]->canRead());
        $this->assertFalse($permissions[0]->canCreate());
        $this->assertFalse($permissions[0]->canUpdate());
        $this->assertFalse($permissions[0]->canDelete());
        $this->assertFalse($permissions[0]->isSelf());
    }

    public function testGetDataGroupPermissionsWithApi(): void
    {
        $supervisorUserRole = $this->getEntityReference(UserRole::class, 3);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$supervisorUserRole]);
        $dataGroupPermissionFilterParams->setWithApiDataGroups(true);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions[0]->canRead());
        $this->assertFalse($permissions[0]->canCreate());
        $this->assertFalse($permissions[0]->canUpdate());
        $this->assertFalse($permissions[0]->canDelete());
        $this->assertFalse($permissions[0]->isSelf());

        $this->assertTrue($permissions[1]->canRead());
        $this->assertFalse($permissions[1]->canCreate());
        $this->assertFalse($permissions[1]->canUpdate());
        $this->assertTrue($permissions[1]->canDelete());
        $this->assertFalse($permissions[1]->isSelf());
    }

    public function testGetDataGroupPermissionsNotOnlyAccessible(): void
    {
        $supervisorUserRole = $this->getEntityReference(UserRole::class, 3);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$supervisorUserRole]);
        $dataGroupPermissionFilterParams->setOnlyAccessible(false);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(2, $permissions);
        $this->assertEquals('emergency_contacts', $permissions[0]->getDataGroup()->getName());
        $this->assertEquals('contact_details', $permissions[1]->getDataGroup()->getName());
    }

    public function testGetDataGroupPermissionsWithOnlyAccessible(): void
    {
        $supervisorUserRole = $this->getEntityReference(UserRole::class, 3);
        $dataGroupPermissionFilterParams = new DataGroupPermissionFilterParams();
        $dataGroupPermissionFilterParams->setUserRoles([$supervisorUserRole]);
        $dataGroupPermissionFilterParams->setOnlyAccessible(true);
        $permissions = $this->dao->getDataGroupPermissions($dataGroupPermissionFilterParams);

        $this->assertCount(1, $permissions);
        $this->assertEquals('emergency_contacts', $permissions[0]->getDataGroup()->getName());
    }
}
