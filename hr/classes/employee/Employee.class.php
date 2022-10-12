<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace hr\employee;

class Employee extends \identity\Partner {

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
                'description'       => 'Force relationship to Employee'
            ],

            'date_start' => [
                'type'              => 'date',
                'description'       => 'Date of the first day of work.',
                'required'          => true
            ],

            'date_end' => [
                'type'              => 'date',
                'description'       => 'Date of the last day of work.',
                'help'              => 'Date at which the contract ends (known in advance for fixed-term or unknown for permanent).'
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Marks the employee as currently active within the organisation.',
                'default'           => true
            ],

            'absences_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\absence\Absence',
                'foreign_field'     => 'employee_id',
                'description'       => 'Absences relating to the employee.',
            ]

        ];
    }

    public function getUnique() {
        return [
            ['owner_identity_id', 'partner_identity_id', 'role_id']
        ];
    }

}