<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\role;

use equal\orm\Model;
use hr\employee\Employee;

class RoleAssignment extends Model {

    private static $previous_employee_ids = [];

    public static function getName() {
        return 'Role assignment';
    }

    public static function getDescription() {
        return 'A role assignment links an employee to a role and identifies their primary role.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Short description of the role assignment.',
                'function'          => 'calcName',
                'readonly'          => true
            ],

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => 'Employee the role is assigned to.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'role_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\role\Role',
                'description'       => 'Role assigned to the employee.',
                'ondelete'          => 'cascade',
                'dependents'        => ['role_code'],
                'required'          => true
            ],

            'role_code' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['role_id' => 'code'],
                'description'       => 'Code of the assigned role.',
                'store'             => true,
                'instant'           => true,
                'readonly'          => true
            ],

            'is_primary' => [
                'type'              => 'boolean',
                'description'       => 'Marks this assignment as the employee primary role.',
                'default'           => false
            ]

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'synchronize_primary_role' => [
                'description'       => 'Ensure a single primary role and refresh the related employee.',
                'policies'          => [],
                'function'          => 'doSynchronizePrimaryRole'
            ]
        ]);
    }

    public function getUniques(): array {
        return [['employee_id', 'role_id']];
    }

    public static function calcName($self): array {
        $result = [];
        $self->read([
            'employee_id' => ['name'],
            'role_id'     => ['name']
        ]);
        foreach($self as $id => $assignment) {
            $parts = array_filter([
                $assignment['employee_id']['name'] ?? '',
                $assignment['role_id']['name'] ?? ''
            ]);
            $result[$id] = implode(' - ', $parts);
        }

        return $result;
    }

    protected static function onbeforeupdate($self, $values) {
        if(!array_key_exists('employee_id', $values)) {
            return;
        }

        $self->read(['employee_id']);
        foreach($self as $id => $assignment) {
            if($assignment['employee_id']) {
                self::$previous_employee_ids[$id] = $assignment['employee_id'];
            }
        }
    }

    protected static function onafterupdate($self, $values) {
        if(!array_intersect(['employee_id', 'role_id', 'is_primary'], array_keys($values))) {
            return;
        }

        $self->do('synchronize_primary_role');

        $previous_employee_ids = [];
        foreach($self as $id => $assignment) {
            if(isset(self::$previous_employee_ids[$id])) {
                $previous_employee_ids[] = self::$previous_employee_ids[$id];
                unset(self::$previous_employee_ids[$id]);
            }
        }

        if($previous_employee_ids) {
            Employee::ids(array_values(array_unique($previous_employee_ids)))
                ->do('refresh_primary_role');
        }
    }

    protected static function onafterinstantiate($self) {
        $self->do('synchronize_primary_role');
    }

    protected static function onbeforedelete($self) {
        $self->read(['employee_id']);
        foreach($self as $id => $assignment) {
            if($assignment['employee_id']) {
                self::$previous_employee_ids[$id] = $assignment['employee_id'];
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
                ->do('refresh_primary_role');
        }
    }

    protected static function doSynchronizePrimaryRole($self) {
        $self->read(['employee_id', 'is_primary']);

        $employee_ids = [];
        foreach($self as $id => $assignment) {
            if(!$assignment['employee_id']) {
                continue;
            }

            $employee_ids[] = $assignment['employee_id'];
            if($assignment['is_primary']) {
                self::search([
                    ['employee_id', '=', $assignment['employee_id']],
                    ['is_primary', '=', true],
                    ['id', '<>', $id]
                ])->update(['is_primary' => false]);
            }
        }

        if($employee_ids) {
            Employee::ids(array_values(array_unique($employee_ids)))
                ->do('refresh_primary_role');
        }
    }
}
