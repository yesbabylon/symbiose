<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr;

use hr\employee\Employee;

class Team extends \equal\orm\Model {

    public static function getName() {
        return 'Team';
    }

    public static function getDescription() {
        return 'A team groups employees and can assign them a common role.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'Official name of the team.',
                'required'          => true
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'Organization the team belongs to.',
                'default'           => 1
            ],

            'employees_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'hr\employee\Employee',
                'foreign_field'     => 'teams_ids',
                'rel_table'         => 'hr_rel_employee_team',
                'rel_foreign_key'   => 'employee_id',
                'rel_local_key'     => 'team_id',
                'description'       => 'Employees who are members of the team.',
                'dependents'        => ['members_count'],
                'domain'            => [['organization_id', '=', 'object.organization_id']],
                'onupdate'          => 'onupdateEmployeesIds'
            ],

            'members_count' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Number of employees in the team.',
                'function'          => 'calcMembersCount',
                'store'             => false,
                'readonly'          => true
            ],

            'role_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\role\Role',
                'description'       => 'Role assigned to the team members.',
                'onupdate'          => 'onupdateRoleId'
            ]

        ];
    }

    public static function getActions(): array {
        return array_merge(parent::getActions(), [
            'sync_members_roles' => [
                'description'       => 'Assign the team role to all current members.',
                'policies'          => [],
                'function'          => 'doSyncMembersRoles'
            ]
        ]);
    }

    protected static function calcMembersCount($self): array {
        $result = [];
        $self->read(['employees_ids']);
        foreach($self as $id => $team) {
            $result[$id] = count($team['employees_ids']);
        }

        return $result;
    }

    protected static function doSyncMembersRoles($self) {
        $self->read(['employees_ids']);
        foreach($self as $team) {
            if(empty($team['employees_ids'])) {
                continue;
            }
            Employee::ids($team['employees_ids'])->do('sync_from_teams');
        }
    }

    protected static function onupdateEmployeesIds($self) {
        $self->do('sync_members_roles');
    }

    protected static function onupdateRoleId($self) {
        $self->do('sync_members_roles');
    }
}
