<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\employee;

use equal\orm\Model;

class EmploymentSalary extends Model {

    public static function getName() {
        return 'Employment salary';
    }

    public static function getDescription() {
        return 'An employment salary records the salary applicable from a given date.';
    }

    public static function getColumns(): array {
        return [

            'employment_contract_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\EmploymentContract',
                'description'       => 'Employment contract the salary applies to.',
                'required'          => true
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'First day on which the salary amount applies.',
                'required'          => true
            ],

            'salary_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Salary amount applicable from the effective date.',
                'required'          => true
            ],

            'change_reason' => [
                'type'              => 'string',
                'selection'         => ['contract', 'individual_increase', 'indexation', 'seniority_adjustment'],
                'default'           => 'contract',
                'description'       => 'Reason for the salary amount change.',
                'required'          => true
            ]

        ];
    }

    public function getUniques(): array {
        return [['employment_contract_id', 'date']];
    }
}
