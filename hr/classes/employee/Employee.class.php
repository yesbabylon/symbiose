<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\employee;

use identity\IdentityFacetAbstract;

class Employee extends IdentityFacetAbstract {

    public static function getName() {
        return 'Employee';
    }

    public static function getDescription() {
        return "An employee is relationship relating to contract that has been made between an identity and a company.";
    }

    public static function getColumns() {

        return [

            'role_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Role',
                'description'       => 'Role assigned to the employee.'
                // #memo - might not be assigned at creation
                // 'required'          => true
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'employee',
                'description'       => 'Force relationship to Employee.'
            ],

            'date_start' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Date of the first day of work.',
                'store'             => true,
                'function'          => 'calcDateStart'
            ],

            'date_end' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Date of the last day of work.',
                'help'              => 'Date at which the contract ends (known in advance for fixed-term or unknown for permanent).',
                'store'             => true,
                'function'          => 'calcDateEnd'
            ],

            'absences_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\absence\Absence',
                'foreign_field'     => 'employee_id',
                'description'       => 'Absences relating to the employee.',
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\employee\Contract',
                'foreign_field'     => 'employee_id',
                'description'       => 'Absences relating to the employee.',
            ]

        ];
    }

    public function getUniques(): array {
        return [['identity_id']];
    }

    public static function calcDateStart($self): array {
        $result = [];
        $now = time();
        $self->read(['contracts_ids' => ['date_start', 'date_end']]);
        foreach($self as $id => $employee) {
            $current_contract = null;
            foreach($employee['contracts_ids'] as $contract) {
                if($contract['date_start'] >= $now || (is_null($contract['date_end']) || $contract['date_end'] >= $now)) {
                    $current_contract = $contract;
                    break;
                }
            }

            if($current_contract) {
                $result[$id] = $current_contract['date_start'];
            }
        }

        return $result;
    }

    public static function calcDateEnd($self): array {
        $result = [];
        $now = time();
        $self->read(['contracts_ids' => ['date_start', 'date_end']]);
        foreach($self as $id => $employee) {
            $current_contract = null;
            foreach($employee['contracts_ids'] as $contract) {
                if($contract['date_start'] >= $now || (is_null($contract['date_end']) || $contract['date_end'] >= $now)) {
                    $current_contract = $contract;
                    break;
                }
            }

            if($current_contract) {
                $result[$id] = $current_contract['date_end'];
            }
        }

        return $result;
    }
}
