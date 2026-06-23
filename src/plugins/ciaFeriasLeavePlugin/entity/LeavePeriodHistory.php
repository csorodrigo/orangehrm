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

namespace CiaFerias\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use CiaFerias\Entity\Decorator\DecoratorTrait;
use CiaFerias\Entity\Decorator\LeavePeriodHistoryDecorator;

/**
 * @method LeavePeriodHistoryDecorator getDecorator()
 *
 * @ORM\Table(name="cia_ferias_leave_period_history")
 * @ORM\Entity
 */
class LeavePeriodHistory
{
    use DecoratorTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var int
     *
     * @ORM\Column(name="leave_period_start_month", type="integer")
     */
    private int $startMonth;

    /**
     * @var int
     *
     * @ORM\Column(name="leave_period_start_day", type="integer")
     */
    private int $startDay;

    /**
     * @var int|null
     *
     * @ORM\Column(name="leave_period_end_month", type="integer", nullable=true)
     */
    private ?int $endMonth = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="leave_period_end_day", type="integer", nullable=true)
     */
    private ?int $endDay = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="date")
     */
    private DateTime $createdAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getStartMonth(): int
    {
        return $this->startMonth;
    }

    /**
     * @param int $startMonth
     */
    public function setStartMonth(int $startMonth): void
    {
        $this->startMonth = $startMonth;
    }

    /**
     * @return int
     */
    public function getStartDay(): int
    {
        return $this->startDay;
    }

    /**
     * @param int $startDay
     */
    public function setStartDay(int $startDay): void
    {
        $this->startDay = $startDay;
    }

    /**
     * @return int|null
     */
    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    /**
     * @param int|null $endMonth
     */
    public function setEndMonth(?int $endMonth): void
    {
        $this->endMonth = $endMonth;
    }

    /**
     * @return int|null
     */
    public function getEndDay(): ?int
    {
        return $this->endDay;
    }

    /**
     * @param int|null $endDay
     */
    public function setEndDay(?int $endDay): void
    {
        $this->endDay = $endDay;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
