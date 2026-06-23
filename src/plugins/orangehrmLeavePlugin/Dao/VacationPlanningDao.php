<?php

namespace OrangeHRM\Leave\Dao;

use OrangeHRM\Core\Dao\BaseDao;

class VacationPlanningDao extends BaseDao
{
    private bool $schemaChecked = false;

    public function getPreference(int $empNumber): array
    {
        $this->ensurePreferenceTable();
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT * FROM ohrm_ca_vacation_preference WHERE emp_number = ?',
            [$empNumber]
        );

        if (!$row) {
            return $this->emptyPreference($empNumber);
        }

        return $this->normalizePreferenceRow($row);
    }

    public function savePreference(int $empNumber, array $preference): array
    {
        $this->ensurePreferenceTable();
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            'INSERT INTO ohrm_ca_vacation_preference (
                emp_number,
                option_a_from,
                option_a_to,
                option_b_from,
                option_b_to,
                option_c_from,
                option_c_to,
                restricted_month,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                option_a_from = VALUES(option_a_from),
                option_a_to = VALUES(option_a_to),
                option_b_from = VALUES(option_b_from),
                option_b_to = VALUES(option_b_to),
                option_c_from = VALUES(option_c_from),
                option_c_to = VALUES(option_c_to),
                restricted_month = VALUES(restricted_month),
                updated_at = NOW()',
            [
                $empNumber,
                $preference['optionA']['fromDate'] ?? null,
                $preference['optionA']['toDate'] ?? null,
                $preference['optionB']['fromDate'] ?? null,
                $preference['optionB']['toDate'] ?? null,
                $preference['optionC']['fromDate'] ?? null,
                $preference['optionC']['toDate'] ?? null,
                $preference['restrictedMonth'] ?? null,
            ]
        );

        return $this->getPreference($empNumber);
    }

    public function getPlanningEmployees(?int $empNumber = null, ?int $subunitId = null): array
    {
        $this->ensurePreferenceTable();
        $connection = $this->getEntityManager()->getConnection();
        $where = ['1 = 1'];
        $params = [];

        if ($empNumber !== null) {
            $where[] = 'e.emp_number = ?';
            $params[] = $empNumber;
        }

        if ($subunitId !== null) {
            $where[] = 'e.work_station = ?';
            $params[] = $subunitId;
        }

        $rows = $connection->fetchAllAssociative(
            'SELECT
                e.emp_number,
                e.emp_firstname,
                e.emp_middle_name,
                e.emp_lastname,
                e.joined_date,
                jt.job_title,
                s.name AS subunit,
                p.option_a_from,
                p.option_a_to,
                p.option_b_from,
                p.option_b_to,
                p.option_c_from,
                p.option_c_to,
                p.restricted_month
            FROM hs_hr_employee e
            LEFT JOIN ohrm_job_title jt ON jt.id = e.job_title_code
            LEFT JOIN ohrm_subunit s ON s.id = e.work_station
            LEFT JOIN ohrm_ca_vacation_preference p ON p.emp_number = e.emp_number
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.emp_firstname, e.emp_lastname',
            $params
        );

        return array_map(fn (array $row) => $this->normalizeEmployeeRow($row), $rows);
    }

    private function normalizeEmployeeRow(array $row): array
    {
        $empNumber = (int)$row['emp_number'];
        return [
            'empNumber' => $empNumber,
            'name' => trim(
                implode(
                    ' ',
                    array_filter([$row['emp_firstname'] ?? '', $row['emp_middle_name'] ?? '', $row['emp_lastname'] ?? ''])
                )
            ),
            'joinedDate' => $row['joined_date'] ?? null,
            'jobTitle' => $row['job_title'] ?? '',
            'subunit' => $row['subunit'] ?? '',
            'takenDays' => $this->getTakenDays($empNumber),
            'scheduled' => $this->getScheduledLeaves($empNumber),
            'preferences' => $this->preferenceOptionsFromRow($row),
            'restrictedMonth' => isset($row['restricted_month']) ? (int)$row['restricted_month'] : null,
        ];
    }

    private function getTakenDays(int $empNumber): float
    {
        $value = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COALESCE(SUM(length_days), 0)
            FROM ohrm_leave
            WHERE emp_number = ? AND status = 3',
            [$empNumber]
        );
        return (float)$value;
    }

    private function getScheduledLeaves(int $empNumber): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT MIN(date) AS fromDate, MAX(date) AS toDate, status
            FROM ohrm_leave
            WHERE emp_number = ? AND status IN (1, 2, 3)
            GROUP BY leave_request_id, status
            ORDER BY fromDate',
            [$empNumber]
        );
    }

    private function normalizePreferenceRow(array $row): array
    {
        return [
            'empNumber' => (int)$row['emp_number'],
            'optionA' => $this->optionFromRow($row, 'option_a'),
            'optionB' => $this->optionFromRow($row, 'option_b'),
            'optionC' => $this->optionFromRow($row, 'option_c'),
            'restrictedMonth' => isset($row['restricted_month']) ? (int)$row['restricted_month'] : null,
        ];
    }

    private function preferenceOptionsFromRow(array $row): array
    {
        return array_values(
            array_filter(
                [
                    $this->judgeOptionFromRow($row, 'A', 'option_a'),
                    $this->judgeOptionFromRow($row, 'B', 'option_b'),
                    $this->judgeOptionFromRow($row, 'C', 'option_c'),
                ]
            )
        );
    }

    private function judgeOptionFromRow(array $row, string $label, string $prefix): ?array
    {
        $option = $this->optionFromRow($row, $prefix);
        if ($option['fromDate'] === null || $option['toDate'] === null) {
            return null;
        }

        return [
            'label' => $label,
            'fromDate' => $option['fromDate'],
            'toDate' => $option['toDate'],
        ];
    }

    private function optionFromRow(array $row, string $prefix): array
    {
        return [
            'fromDate' => $row[$prefix . '_from'] ?? null,
            'toDate' => $row[$prefix . '_to'] ?? null,
        ];
    }

    private function emptyPreference(int $empNumber): array
    {
        return [
            'empNumber' => $empNumber,
            'optionA' => ['fromDate' => null, 'toDate' => null],
            'optionB' => ['fromDate' => null, 'toDate' => null],
            'optionC' => ['fromDate' => null, 'toDate' => null],
            'restrictedMonth' => null,
        ];
    }

    private function ensurePreferenceTable(): void
    {
        if ($this->schemaChecked) {
            return;
        }

        $this->getEntityManager()->getConnection()->executeStatement(
            'CREATE TABLE IF NOT EXISTS ohrm_ca_vacation_preference (
                emp_number INT NOT NULL,
                option_a_from DATE NULL,
                option_a_to DATE NULL,
                option_b_from DATE NULL,
                option_b_to DATE NULL,
                option_c_from DATE NULL,
                option_c_to DATE NULL,
                restricted_month TINYINT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (emp_number)
            )'
        );
        $this->schemaChecked = true;
    }
}
