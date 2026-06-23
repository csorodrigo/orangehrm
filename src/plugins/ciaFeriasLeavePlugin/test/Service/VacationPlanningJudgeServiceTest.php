<?php

namespace CiaFerias\Tests\Leave\Service;

use DateTime;
use CiaFerias\Leave\Service\VacationPlanningJudgeService;
use CiaFerias\Tests\Util\TestCase;

class VacationPlanningJudgeServiceTest extends TestCase
{
    public function testPreferenceAReceivesHigherScoreThanPreferenceBAndC(): void
    {
        $service = new VacationPlanningJudgeService();
        $plans = $service->buildPlan(
            [
                $this->employee(1, 'Ana', '2024-01-10', 'Analista', 'RH', [
                    ['label' => 'A', 'fromDate' => '2026-03-02', 'toDate' => '2026-03-31'],
                    ['label' => 'B', 'fromDate' => '2026-05-04', 'toDate' => '2026-06-02'],
                    ['label' => 'C', 'fromDate' => '2026-08-03', 'toDate' => '2026-09-01'],
                ]),
            ],
            new DateTime('2026-01-05')
        );

        $this->assertSame('A', $plans[0]['recommendation']['matchedPreference']);
        $this->assertGreaterThanOrEqual(9.0, $plans[0]['score']);
    }

    public function testRestrictedMonthStronglyPenalizesRecommendation(): void
    {
        $service = new VacationPlanningJudgeService();
        $plans = $service->buildPlan(
            [
                $this->employee(1, 'Ana', '2024-01-10', 'Analista', 'RH', [
                    ['label' => 'A', 'fromDate' => '2026-03-02', 'toDate' => '2026-03-31'],
                ], 3),
            ],
            new DateTime('2026-01-05')
        );

        $this->assertLessThan(9.0, $plans[0]['score']);
        $this->assertContains('Periodo esta no mes restrito pelo colaborador', $plans[0]['reasons']);
    }

    public function testSameJobTitleConflictChoosesMostLegallyUrgentEmployee(): void
    {
        $service = new VacationPlanningJudgeService();
        $plans = $service->buildPlan(
            [
                $this->employee(1, 'Ana', '2024-01-10', 'Operador', 'Producao', [
                    ['label' => 'A', 'fromDate' => '2026-03-02', 'toDate' => '2026-03-31'],
                ]),
                $this->employee(2, 'Bruno', '2023-07-10', 'Operador', 'Producao', [
                    ['label' => 'A', 'fromDate' => '2026-03-02', 'toDate' => '2026-03-31'],
                    ['label' => 'B', 'fromDate' => '2026-04-06', 'toDate' => '2026-05-05'],
                ]),
            ],
            new DateTime('2026-01-05')
        );

        $this->assertSame(2, $plans[0]['employee']['empNumber']);
        $this->assertSame('A', $plans[0]['recommendation']['matchedPreference']);
        $this->assertSame(1, $plans[1]['employee']['empNumber']);
        $this->assertContains('Conflito por cargo resolvido: prioridade ficou com colaborador mais proximo do vencimento', $plans[1]['reasons']);
    }

    public function testEmployeeWithLessThanTwelveMonthsIsNotActionable(): void
    {
        $service = new VacationPlanningJudgeService();
        $plans = $service->buildPlan(
            [
                $this->employee(1, 'Ana', '2025-08-10', 'Analista', 'RH', [
                    ['label' => 'A', 'fromDate' => '2026-03-02', 'toDate' => '2026-03-31'],
                ]),
            ],
            new DateTime('2026-01-05')
        );

        $this->assertSame('nao_elegivel', $plans[0]['risk']);
        $this->assertNull($plans[0]['recommendation']);
        $this->assertLessThan(9.0, $plans[0]['score']);
    }

    private function employee(
        int $empNumber,
        string $name,
        string $joinedDate,
        string $jobTitle,
        string $subunit,
        array $preferences,
        ?int $restrictedMonth = null
    ): array {
        return [
            'empNumber' => $empNumber,
            'name' => $name,
            'joinedDate' => $joinedDate,
            'jobTitle' => $jobTitle,
            'subunit' => $subunit,
            'takenDays' => 0.0,
            'scheduled' => [],
            'preferences' => $preferences,
            'restrictedMonth' => $restrictedMonth,
        ];
    }
}
