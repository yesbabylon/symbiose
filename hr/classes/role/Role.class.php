<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\role;

use equal\orm\Model;

class Role extends Model {

    public static function getName() {
        return 'Role';
    }

    public static function getDescription() {
        return 'A role describes a set of responsibilities that can be assigned to employees.';
    }

    public static function getModelTable(): string {
        return 'hr_employee_role';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Official name of the role.',
                'multilang'         => true,
                'store'             => true,
                'readonly'          => true,
                'function'          => 'calcName'
            ],

            'code' => [
                'type'              => 'string',
                'description'       => 'Unique mnemonic code assigned to the role.',
                'unique'            => true,
                'dependents'        => ['name'],
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Details about the role.',
                'dependents'        => ['name'],
                'multilang'         => true
            ],

            'role_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\role\RoleAssignment',
                'foreign_field'     => 'role_id',
                'description'       => 'Employees assigned to the role.'
            ],

            'role_permissions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'hr\role\RoleGrant',
                'foreign_field'     => 'role_id',
                'description'       => 'Workflow permissions granted by the role.'
            ]

        ];
    }

    protected static function calcName($self, $lang) {
        $result = [];
        $self->read(['code', 'description'], $lang);
        foreach($self as $id => $role) {
            $result[$id] = $role['code'] . ' - ' . $role['description'];
        }
        return $result;
    }
}
