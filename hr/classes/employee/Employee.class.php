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
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'hr\role\Role',
                'description'       => 'Primary role assigned to the employee.',
                'function'          => 'calcRoleId',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'role_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\role\RoleAssignment',
                'foreign_field'     => 'employee_id',
                'description'       => 'Roles assigned to the employee.'
            ],

            'employee_number' => [
                'type'              => 'string',
                'description'       => 'Internal number assigned to the employee.'
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'employee',
                'description'       => 'Force relationship to Employee.'
            ],

            'is_active' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Is the employee covered by a contract for the current day?',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true,
                'function'          => 'calcIsActive'
            ],

            'first_employment_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Date of the employee\'s first day of employment.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true,
                'function'          => 'calcFirstEmploymentDate'
            ],

            'absences_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\absence\Absence',
                'foreign_field'     => 'employee_id',
                'description'       => 'Absences relating to the employee.',
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\employee\EmploymentContract',
                'foreign_field'     => 'employee_id',
                'description'       => 'Employment contracts relating to the employee.',
            ]

        ];
    }

    public static function getActions() {
        return array_merge(parent::getActions(), [
            'refresh_employment_status' => [
                'description'       => 'Refresh the stored employment status and first employment date.',
                'policies'          => [],
                'function'          => 'doRefreshEmploymentStatus'
            ],
            'refresh_primary_role' => [
                'description'       => 'Refresh the primary role from role assignments.',
                'policies'          => [],
                'function'          => 'doRefreshPrimaryRole'
            ]
        ]);
    }

    public function getUniques(): array {
        return [['identity_id']];
    }

    public static function calcIsActive($self): array {
        $result = [];
        $today = strtotime(date('Y-m-d'));
        $self->read(['contracts_ids' => ['status', 'date_start', 'date_end']]);
        foreach($self as $id => $employee) {
            $result[$id] = false;
            foreach($employee['contracts_ids'] as $contract) {
                if(is_null($contract['date_start'])) {
                    continue;
                }
                if(
                    $contract['status'] === 'active'
                    && $contract['date_start'] <= $today
                    && (is_null($contract['date_end']) || $contract['date_end'] >= $today)
                ) {
                    $result[$id] = true;
                    break;
                }
            }
        }

        return $result;
    }

    public static function calcFirstEmploymentDate($self): array {
        $result = [];
        $self->read(['contracts_ids' => ['status', 'date_start']]);
        foreach($self as $id => $employee) {
            $first_employment_date = null;
            foreach($employee['contracts_ids'] as $contract) {
                if(is_null($contract['date_start'])) {
                    continue;
                }
                if($contract['status'] === 'draft') {
                    continue;
                }
                if(is_null($first_employment_date) || $contract['date_start'] < $first_employment_date) {
                    $first_employment_date = $contract['date_start'];
                }
            }
            $result[$id] = $first_employment_date;
        }

        return $result;
    }

    public static function calcRoleId($self): array {
        $result = [];
        $self->read(['role_assignments_ids' => ['is_primary', 'role_id']]);
        foreach($self as $id => $employee) {
            $result[$id] = null;
            foreach($employee['role_assignments_ids'] as $assignment) {
                if($assignment['is_primary']) {
                    $result[$id] = $assignment['role_id'];
                    break;
                }
            }
        }

        return $result;
    }

    protected static function doRefreshEmploymentStatus($self) {
        $self->update([
            'is_active'             => null,
            'first_employment_date' => null
        ]);
    }

    protected static function doRefreshPrimaryRole($self) {
        $self->update(['role_id' => null]);
    }
}
