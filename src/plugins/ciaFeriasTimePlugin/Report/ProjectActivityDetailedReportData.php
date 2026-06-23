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

namespace CiaFerias\Time\Report;

use CiaFerias\Core\Api\CommonParams;
use CiaFerias\Core\Api\V2\ParameterBag;
use CiaFerias\Core\Report\ReportData;
use CiaFerias\Core\Traits\Service\DateTimeHelperTrait;
use CiaFerias\Core\Traits\Service\NormalizerServiceTrait;
use CiaFerias\Core\Traits\Service\NumberHelperTrait;
use CiaFerias\I18N\Traits\Service\I18NHelperTrait;
use CiaFerias\Time\Api\Model\ProjectActivityModel;
use CiaFerias\Time\Dto\ProjectActivityDetailedReportSearchFilterParams;
use CiaFerias\Time\Traits\Service\ProjectServiceTrait;

class ProjectActivityDetailedReportData implements ReportData
{
    use ProjectServiceTrait;
    use NumberHelperTrait;
    use DateTimeHelperTrait;
    use NormalizerServiceTrait;
    use I18NHelperTrait;

    /**
     * @var ProjectActivityDetailedReportSearchFilterParams
     */
    private ProjectActivityDetailedReportSearchFilterParams $filterParams;

    public function __construct(ProjectActivityDetailedReportSearchFilterParams $filterParams)
    {
        $this->filterParams = $filterParams;
    }

    /**
     * @inheritDoc
     */
    public function normalize(): array
    {
        $employees = $this->getProjectService()
            ->getProjectDao()
            ->getProjectActivityDetailedReportCriteriaList($this->filterParams);
        $result = [];
        foreach ($employees as $employee) {
            $termination = $employee['terminationId'];
            $result[] = [
                ProjectActivityReport::PARAMETER_EMPLOYEE_NAME => $termination === null ? $employee['fullName'] : $employee['fullName'] . ' ' . $this->getI18NHelper()->transBySource('(Past Employee)'),
                ProjectReport::PARAMETER_TIME => $this->getNumberHelper()
                    ->numberFormat((float)$employee['totalDuration'] / 3600, 2),
            ];
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMeta(): ?ParameterBag
    {
        $projectActivity = $this->getProjectService()
            ->getProjectActivityDao()
            ->getProjectActivityByProjectIdAndProjectActivityId(
                $this->filterParams->getProjectId(),
                $this->filterParams->getProjectActivityId()
            );

        $total = $this->getProjectService()
            ->getProjectDao()
            ->getTotalDurationForProjectActivityDetailedReport($this->filterParams);

        return new ParameterBag(
            [
                CommonParams::PARAMETER_TOTAL => $this->getProjectService()
                    ->getProjectDao()
                    ->getProjectReportActivityDetailedCriteriaListCount($this->filterParams),
                'sum' => [
                    'hours' => floor($total / 3600),
                    'minutes' => ($total / 60) % 60,
                    'label' => $this->getNumberHelper()->numberFormat($total / 3600, 2),
                ],
                'projectActivity' => $this->getNormalizerService()
                    ->normalize(ProjectActivityModel::class, $projectActivity),
            ]
        );
    }
}
