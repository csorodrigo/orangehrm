<?php

namespace CiaFerias\Leave\Service;

use DateInterval;
use DateTime;

class VacationPlanningJudgeService
{
    private const VACATION_DAYS = 30;

    public function buildPlan(array $employees, ?DateTime $today = null): array
    {
        $today = $today ?? new DateTime();
        $plans = array_map(
            fn (array $employee) => $this->buildEmployeePlan($employee, $today),
            $employees
        );

        $plans = $this->resolveJobTitleConflicts($plans);

        usort($plans, function (array $left, array $right): int {
            $leftDue = $left['legal']['concessionEnd'] ?? '9999-12-31';
            $rightDue = $right['legal']['concessionEnd'] ?? '9999-12-31';
            if ($leftDue === $rightDue) {
                return $right['score'] <=> $left['score'];
            }
            return strcmp($leftDue, $rightDue);
        });

        return array_values($plans);
    }

    private function buildEmployeePlan(array $employee, DateTime $today): array
    {
        $joinedDate = $this->dateOrNull($employee['joinedDate'] ?? null);
        if (!$joinedDate instanceof DateTime) {
            return $this->nonActionablePlan($employee, 'sem_data_admissao', 'Cadastro sem data de inicio');
        }

        $acquisitionStart = clone $joinedDate;
        while ((clone $acquisitionStart)->add(new DateInterval('P2Y')) <= $today) {
            $acquisitionStart->add(new DateInterval('P1Y'));
        }

        $acquisitionEnd = (clone $acquisitionStart)->add(new DateInterval('P1Y'))->sub(new DateInterval('P1D'));
        $concessionStart = (clone $acquisitionEnd)->add(new DateInterval('P1D'));
        $concessionEnd = (clone $concessionStart)->add(new DateInterval('P1Y'))->sub(new DateInterval('P1D'));

        $legal = [
            'acquisitionStart' => $acquisitionStart->format('Y-m-d'),
            'acquisitionEnd' => $acquisitionEnd->format('Y-m-d'),
            'concessionStart' => $concessionStart->format('Y-m-d'),
            'concessionEnd' => $concessionEnd->format('Y-m-d'),
        ];

        if ($today < $concessionStart) {
            return [
                'employee' => $this->employeeSummary($employee),
                'legal' => $legal,
                'history' => $this->historySummary($employee),
                'preferences' => $this->preferencesSummary($employee),
                'recommendation' => null,
                'score' => 4.0,
                'risk' => 'nao_elegivel',
                'reasons' => ['Colaborador ainda nao completou 12 meses de periodo aquisitivo'],
            ];
        }

        $recommendation = $this->selectBestWindow($employee, $today, $concessionEnd);
        $score = $recommendation['score'];
        $reasons = $recommendation['reasons'];
        $risk = $this->legalRisk($today, $concessionEnd);

        if ($risk === 'vencido') {
            $score = min($score, 8.9);
            $reasons[] = 'Periodo concessivo ja vencido';
        }

        return [
            'employee' => $this->employeeSummary($employee),
            'legal' => $legal,
            'history' => $this->historySummary($employee),
            'preferences' => $this->preferencesSummary($employee),
            'recommendation' => $recommendation['window'],
            'score' => round(max(0.0, min(10.0, $score)), 1),
            'risk' => $risk,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function selectBestWindow(array $employee, DateTime $today, DateTime $concessionEnd): array
    {
        $candidates = [];
        foreach (($employee['preferences'] ?? []) as $preference) {
            $fromDate = $this->dateOrNull($preference['fromDate'] ?? null);
            $toDate = $this->dateOrNull($preference['toDate'] ?? null);
            if (!$fromDate instanceof DateTime || !$toDate instanceof DateTime) {
                continue;
            }

            $candidates[] = $this->scoreWindow(
                $preference['label'] ?? null,
                $fromDate,
                $toDate,
                $employee,
                $today,
                $concessionEnd
            );
        }

        if (empty($candidates)) {
            $fromDate = (clone $today)->add(new DateInterval('P30D'));
            if ($fromDate < $today) {
                $fromDate = clone $today;
            }
            $toDate = (clone $fromDate)->add(new DateInterval('P29D'));
            $candidates[] = $this->scoreWindow(null, $fromDate, $toDate, $employee, $today, $concessionEnd);
        }

        usort($candidates, fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        return $candidates[0];
    }

    private function scoreWindow(
        ?string $matchedPreference,
        DateTime $fromDate,
        DateTime $toDate,
        array $employee,
        DateTime $today,
        DateTime $concessionEnd
    ): array {
        $score = 10.0;
        $reasons = [];

        if ($fromDate < (clone $today)->add(new DateInterval('P30D'))) {
            $score -= 1.0;
            $reasons[] = 'Periodo com menos de 30 dias de antecedencia';
        }

        if ($toDate > $concessionEnd) {
            $score = min($score, 8.9);
            $reasons[] = 'Periodo passa do vencimento maximo legal';
        }

        $days = $fromDate->diff($toDate)->days + 1;
        if ($days < 14) {
            $score = min($score, 8.5);
            $reasons[] = 'Periodo principal menor que 14 dias';
        }

        $restrictedMonth = $employee['restrictedMonth'] ?? null;
        if ($restrictedMonth !== null && (int)$fromDate->format('n') === (int)$restrictedMonth) {
            $score -= 2.0;
            $reasons[] = 'Periodo esta no mes restrito pelo colaborador';
        }

        if ($matchedPreference === 'A') {
            $score += 0.0;
            $reasons[] = 'Atende preferencia A do colaborador';
        } elseif ($matchedPreference === 'B') {
            $score -= 0.6;
            $reasons[] = 'Atende preferencia B do colaborador';
        } elseif ($matchedPreference === 'C') {
            $score -= 1.0;
            $reasons[] = 'Atende preferencia C do colaborador';
        } else {
            $score -= 1.4;
            $reasons[] = 'Sem preferencia cadastrada para este periodo';
        }

        return [
            'window' => [
                'fromDate' => $fromDate->format('Y-m-d'),
                'toDate' => $toDate->format('Y-m-d'),
                'matchedPreference' => $matchedPreference,
            ],
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function resolveJobTitleConflicts(array $plans): array
    {
        foreach ($plans as $index => $plan) {
            if ($plan['recommendation'] === null) {
                continue;
            }

            foreach ($plans as $otherIndex => $otherPlan) {
                if ($index >= $otherIndex || $otherPlan['recommendation'] === null) {
                    continue;
                }

                if ($plan['employee']['jobTitle'] === '' || $plan['employee']['jobTitle'] !== $otherPlan['employee']['jobTitle']) {
                    continue;
                }

                if (!$this->windowsOverlap($plan['recommendation'], $otherPlan['recommendation'])) {
                    continue;
                }

                $loserIndex = strcmp($plan['legal']['concessionEnd'], $otherPlan['legal']['concessionEnd']) <= 0
                    ? $otherIndex
                    : $index;
                $plans[$loserIndex]['score'] = round(min($plans[$loserIndex]['score'], 8.8), 1);
                $plans[$loserIndex]['reasons'][] = 'Conflito por cargo resolvido: prioridade ficou com colaborador mais proximo do vencimento';
                $plans[$loserIndex]['conflicts'][] = [
                    'type' => 'jobTitle',
                    'jobTitle' => $plans[$loserIndex]['employee']['jobTitle'],
                ];
            }
        }

        return $plans;
    }

    private function windowsOverlap(array $left, array $right): bool
    {
        return $left['fromDate'] <= $right['toDate'] && $right['fromDate'] <= $left['toDate'];
    }

    private function nonActionablePlan(array $employee, string $risk, string $reason): array
    {
        return [
            'employee' => $this->employeeSummary($employee),
            'legal' => null,
            'history' => $this->historySummary($employee),
            'preferences' => $this->preferencesSummary($employee),
            'recommendation' => null,
            'score' => 0.0,
            'risk' => $risk,
            'reasons' => [$reason],
        ];
    }

    private function employeeSummary(array $employee): array
    {
        return [
            'empNumber' => (int)($employee['empNumber'] ?? 0),
            'name' => $employee['name'] ?? '',
            'joinedDate' => $employee['joinedDate'] ?? null,
            'jobTitle' => $employee['jobTitle'] ?? '',
            'subunit' => $employee['subunit'] ?? '',
        ];
    }

    private function historySummary(array $employee): array
    {
        return [
            'takenDays' => (float)($employee['takenDays'] ?? 0),
            'scheduled' => $employee['scheduled'] ?? [],
        ];
    }

    private function preferencesSummary(array $employee): array
    {
        return [
            'options' => $employee['preferences'] ?? [],
            'restrictedMonth' => $employee['restrictedMonth'] ?? null,
        ];
    }

    private function legalRisk(DateTime $today, DateTime $concessionEnd): string
    {
        if ($today > $concessionEnd) {
            return 'vencido';
        }
        if ((clone $today)->add(new DateInterval('P60D')) >= $concessionEnd) {
            return 'urgente';
        }
        if ((clone $today)->add(new DateInterval('P120D')) >= $concessionEnd) {
            return 'atencao';
        }
        return 'ok';
    }

    private function dateOrNull($date): ?DateTime
    {
        if ($date instanceof DateTime) {
            return clone $date;
        }
        if (is_string($date) && $date !== '') {
            return new DateTime($date);
        }
        return null;
    }
}
