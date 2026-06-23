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

namespace CiaFerias\Core\Authorization\UserRole;

use CiaFerias\Admin\Service\JobTitleService;
use CiaFerias\Admin\Service\LocationService;
use CiaFerias\Dashboard\Traits\Service\QuickLaunchServiceTrait;
use CiaFerias\Entity\Employee;
use CiaFerias\Entity\JobSpecificationAttachment;
use CiaFerias\Entity\Location;
use CiaFerias\Entity\PerformanceReview;
use CiaFerias\Performance\Traits\Service\PerformanceReviewServiceTrait;
use CiaFerias\Pim\Traits\Service\EmployeeServiceTrait;

class SupervisorUserRole extends AbstractUserRole
{
    use EmployeeServiceTrait;
    use PerformanceReviewServiceTrait;
    use QuickLaunchServiceTrait;

    protected ?LocationService $locationService = null;
    protected ?JobTitleService $jobTitleService = null;

    /**
     * @return LocationService
     */
    protected function getLocationService(): LocationService
    {
        if (!$this->locationService instanceof LocationService) {
            $this->locationService = new LocationService();
        }
        return $this->locationService;
    }

    /**
     * @return JobTitleService
     */
    protected function getJobTitleService(): JobTitleService
    {
        if (!$this->jobTitleService instanceof JobTitleService) {
            $this->jobTitleService = new JobTitleService();
        }
        return $this->jobTitleService;
    }

    /**
     * @inheritDoc
     */
    protected function getAccessibleIdsForEntity(string $entityType, array $requiredPermissions = []): array
    {
        switch ($entityType) {
            case Employee::class:
                return $this->getAccessibleEmployeeIds($requiredPermissions);
            case Location::class:
                return $this->getAccessibleLocationIds($requiredPermissions);
            case PerformanceReview::class:
                return $this->getAccessibleReviewIds($requiredPermissions);
            case JobSpecificationAttachment::class:
                return $this->getAccessibleJobSpecificationAttachmentIds($requiredPermissions);
            default:
                return [];
        }
    }

    /**
     * @param array $requiredPermissions
     * @return int[]
     */
    protected function getAccessibleEmployeeIds(array $requiredPermissions = []): array
    {
        $empNumbers = [];
        $empNumber = $this->getEmployeeNumber();
        if (!empty($empNumber)) {
            $empNumbers = $this->getEmployeeService()->getSubordinateIdListBySupervisorId($empNumber);
        }
        return $empNumbers;
    }

    /**
     * @param array $requiredPermissions
     * @return int[]
     * @todo
     */
    protected function getAccessibleLocationIds(array $requiredPermissions = []): array
    {
        $empNumbers = $this->getAccessibleEmployeeIds();
        return $this->getLocationService()->getLocationIdsForEmployees($empNumbers);
    }

    /**
     * @param array $requiredPermissions
     * @return int[]
     */
    protected function getAccessibleReviewIds(array $requiredPermissions = []): array
    {
        $empNumber = $this->getEmployeeNumber();
        return $this->getPerformanceReviewService()->getPerformanceReviewDao()->getReviewIdsForSupervisorReviewer($empNumber);
    }

    /**
     * @param array $requiredPermissions
     * @return int[]
     */
    protected function getAccessibleJobSpecificationAttachmentIds(array $requiredPermissions = []): array
    {
        $empNumbers = $this->getAccessibleEmployeeIds();
        return $this->getJobTitleService()->getJobSpecificationAttachmentIdsByEmpNumbers($empNumbers);
    }

    /**
     * @inheritDoc
     */
    public function getAccessibleQuickLaunchList(array $requiredPermissions): array
    {
        return $this->getQuickLaunchService()
            ->getQuickLaunchDao()
            ->getQuickLaunchList();
    }
}
