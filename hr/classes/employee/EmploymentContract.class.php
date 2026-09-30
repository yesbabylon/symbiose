<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\employee;

use equal\orm\Model;

class EmploymentContract extends Model {

    private static $previous_employee_ids = [];

    public static function getName() {
        return 'Employment contract';
    }

    public static function getDescription() {
        return 'An employment contract defines the working and salary conditions agreed with an employee.';
    }

    public static function getModelTable(): string {
        return 'hr_employee_contract';
    }

    public static function getColumns(): array {
        return [

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => 'The employee the contract relates to.',
                'required'          => true
            ],

            'contract_type' => [
                'type'              => 'string',
                'selection'         => ['permanent', 'fixed_term', 'temporary', 'internship', 'student'],
                'description'       => 'Type of employment contract.'
            ],

            'job_title' => [
                'type'              => 'string',
                'description'       => 'Job title covered by the employment contract.',
                'required'          => true
            ],

            'work_regime' => [
                'type'              => 'string',
                'selection'         => ['full_time', 'part_time'],
                'description'       => 'Working-time regime of the employment contract.',
                'default'           => 'full_time'
            ],

            'weekly_hours' => [
                'type'              => 'float',
                'description'       => 'Number of working hours per week.',
                'required'          => true
            ],

            'contractual_salary' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Salary amount agreed when the employment contract is established.',
                'required'          => true
            ],

            'salary_period' => [
                'type'              => 'string',
                'selection'         => ['weekly', 'monthly'],
                'default'           => 'monthly',
                'description'       => 'Period covered by salary amounts.'
            ],

            'date_start' => [
                'type'              => 'date',
                'description'       => 'Date of the first day of work.',
                'required'          => true,
                'dependents'        => ['employee_id' => ['first_employment_date', 'is_active']]
            ],

            'date_end' => [
                'type'              => 'date',
                'description'       => 'Date of the last day of work.',
                'help'              => 'Date at which the contract ends (known in advance for fixed-term or unknown for permanent).',
                'dependents'        => ['employee_id' => ['is_active']]
            ],

            'employment_salaries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\employee\EmploymentSalary',
                'foreign_field'     => 'employment_contract_id',
                'description'       => 'Salary history of the employment contract.',
                'order'             => 'date',
                'sort'              => 'desc'
            ],

            'current_salary' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Salary applicable on the current day.',
                'readonly'          => true,
                'function'          => 'calcCurrentSalary'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => ['draft', 'active', 'ended'],
                'default'           => 'draft',
                'description'       => 'Current lifecycle status of the employment contract.',
                'dependents'        => ['employee_id' => ['first_employment_date', 'is_active']]
            ]

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'synchronize_employment_status' => [
                'description'       => 'Refresh the related employee and schedule the contract end synchronization.',
                'policies'          => [],
                'function'          => 'doSynchronizeEmploymentStatus'
            ]
        ]);
    }

    public static function canupdate($self, $values): array {
        $self->read(['employee_id', 'date_start', 'date_end']);

        foreach($self as $id => $contract) {
            $employee_id = array_key_exists('employee_id', $values) ? $values['employee_id'] : $contract['employee_id'];
            $date_start = array_key_exists('date_start', $values) ? $values['date_start'] : $contract['date_start'];
            $date_end = array_key_exists('date_end', $values) ? $values['date_end'] : $contract['date_end'];

            $other_contracts = self::search([
                ['employee_id', '=', $employee_id],
                ['id', '<>', $id]
            ])
                ->read(['date_start', 'date_end']);

            foreach($other_contracts as $other_contract) {
                $starts_before_end = is_null($date_end) || $other_contract['date_start'] <= $date_end;
                $ends_after_start = is_null($other_contract['date_end']) || $other_contract['date_end'] >= $date_start;

                if($starts_before_end && $ends_after_start) {
                    return [
                        'date_start' => [
                            'overlapping_contract' => 'The contract period overlaps another contract for this employee.'
                        ]
                    ];
                }
            }
        }

        return parent::canupdate($self, $values);
    }

    protected static function onbeforeupdate($self, $values) {
        if(!array_key_exists('employee_id', $values)) {
            return;
        }

        $self->read(['employee_id']);
        foreach($self as $id => $contract) {
            if($contract['employee_id']) {
                self::$previous_employee_ids[$id] = $contract['employee_id'];
            }
        }
    }

    protected static function onafterupdate($self, $values) {
        if(!array_intersect(['employee_id', 'status', 'date_start', 'date_end'], array_keys($values))) {
            return;
        }

        $self->do('synchronize_employment_status');

        $previous_employee_ids = [];
        foreach($self as $id => $contract) {
            if(isset(self::$previous_employee_ids[$id])) {
                $previous_employee_ids[] = self::$previous_employee_ids[$id];
                unset(self::$previous_employee_ids[$id]);
            }
        }

        if($previous_employee_ids) {
            Employee::ids(array_values(array_unique($previous_employee_ids)))
                ->do('refresh_employment_status');
        }
    }

    protected static function onafterinstantiate($self) {
        $self->do('synchronize_employment_status');
    }

    protected static function onbeforedelete($self) {
        $self->read(['employee_id']);
        foreach($self as $id => $contract) {
            if($contract['employee_id']) {
                self::$previous_employee_ids[$id] = $contract['employee_id'];
            }
        }
    }

    protected static function onafterdelete($ids) {
        $employee_ids = [];
        foreach($ids as $id) {
            if(isset(self::$previous_employee_ids[$id])) {
                $employee_ids[] = self::$previous_employee_ids[$id];
                unset(self::$previous_employee_ids[$id]);
            }
        }

        if($employee_ids) {
            Employee::ids(array_values(array_unique($employee_ids)))
                ->do('refresh_employment_status');
        }
    }

    protected static function doSynchronizeEmploymentStatus($self, $cron) {
        $self->read(['employee_id', 'status', 'date_end']);

        $now = time();
        $employee_ids = [];

        foreach($self as $id => $contract) {
            $end_task_name = "hr.employment-contract.{$id}.employment-end";
            $cron->cancel($end_task_name);

            if($contract['status'] === 'active' && !is_null($contract['date_end'])) {
                $refresh_date = strtotime('+1 day', $contract['date_end']);
                if($refresh_date <= $now) {
                    self::id($id)->update(['status' => 'ended']);
                    continue;
                }
            }

            if(!$contract['employee_id']) {
                continue;
            }

            $employee_ids[] = $contract['employee_id'];

            if($contract['status'] !== 'active') {
                continue;
            }

            if(!is_null($contract['date_end'])) {
                $cron->schedule(
                    $end_task_name,
                    strtotime('+1 day', $contract['date_end']),
                    'core_model_do',
                    [
                        'id'     => $id,
                        'entity' => 'hr\employee\EmploymentContract',
                        'action' => 'synchronize_employment_status'
                    ]
                );
            }
        }

        if($employee_ids) {
            Employee::ids(array_values(array_unique($employee_ids)))
                ->do('refresh_employment_status');
        }
    }

    public static function calcCurrentSalary($self): array {
        $result = [];
        $today = strtotime(date('Y-m-d'));
        $self->read([
            'contractual_salary',
            'employment_salaries_ids' => ['date', 'salary_amount']
        ]);

        foreach($self as $id => $contract) {
            $current_salary = $contract['contractual_salary'];
            $current_salary_date = null;

            foreach($contract['employment_salaries_ids'] as $employment_salary) {
                if(is_null($employment_salary['date']) || $employment_salary['date'] > $today) {
                    continue;
                }
                if(is_null($current_salary_date) || $employment_salary['date'] > $current_salary_date) {
                    $current_salary = $employment_salary['salary_amount'];
                    $current_salary_date = $employment_salary['date'];
                }
            }

            $result[$id] = $current_salary;
        }

        return $result;
    }
}
