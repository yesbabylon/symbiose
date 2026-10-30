<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\employee;

use core\Group;
use identity\Identity;
use identity\IdentityFacetAbstract;
use identity\User;

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
            ],
            'create_user_account' => [
                'description'       => 'Create a related User account.',
                'policies'          => ['can_create_user_account'],
                'function'          => 'doCreateUserAccount'
            ],
            'disable_user_account' => [
                'description'       => 'Disable the related User account.',
                'policies'          => ['can_manage_user_account'],
                'function'          => 'doDisableUserAccount'
            ],
            'delete_user_account' => [
                'description'       => 'Delete the related User account.',
                'policies'          => ['can_manage_user_account'],
                'function'          => 'doDeleteUserAccount'
            ]
        ]);
    }

    public static function getPolicies() {
        return array_merge(parent::getPolicies(), [
            'can_create_user_account' => [
                'description'       => 'Checks that the current user can create the related User account.',
                'function'          => 'policyCanCreateUserAccount'
            ],
            'can_manage_user_account' => [
                'description'       => 'Checks that the current user can manage the related User account.',
                'function'          => 'policyCanManageUserAccount'
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

    protected static function policyCanCreateUserAccount($self, $user_id, $access): array {
        $result = [];
        $is_allowed = $user_id === EQ_ROOT_USER_ID
            || $access->userHasGroup($user_id, 'admins')
            || $access->userHasGroup($user_id, 'operators');

        if(!$is_allowed) {
            foreach($self->ids() as $id) {
                $result[$id]['not_allowed'] = 'Only administrators and operators can create User accounts.';
            }
        }

        return $result;
    }

    protected static function policyCanManageUserAccount($self, $user_id, $access): array {
        $result = [];
        $is_allowed = $user_id === EQ_ROOT_USER_ID
            || $access->userHasGroup($user_id, 'admins')
            || $access->userHasGroup($user_id, 'operators');

        $self->read(['identity_id' => ['user_id']]);
        foreach($self as $id => $employee) {
            if(!$is_allowed) {
                $result[$id]['not_allowed'] = 'Only administrators and operators can manage User accounts.';
            }
            if(!($employee['identity_id']['user_id'] ?? null)) {
                $result[$id]['missing_user_account'] = 'The Employee has no related User account.';
            }
        }

        return $result;
    }

    protected static function doCreateUserAccount($self, $auth, $orm) {
        $requested_employee_ids = $self->ids();
        trigger_error(
            'APP::Starting User account creation for Employee IDs ['.implode(', ', $requested_employee_ids).'].',
            EQ_REPORT_DEBUG
        );

        $self->read(['identity_id' => ['id', 'name', 'email', 'user_id']]);

        $loaded_employee_ids = $self->ids();
        $missing_employee_ids = array_diff($requested_employee_ids, $loaded_employee_ids);
        if(!empty($missing_employee_ids)) {
            trigger_error(
                'APP::Ignored non-existing Employee IDs ['.implode(', ', $missing_employee_ids).'].',
                EQ_REPORT_WARNING
            );
        }

        $groups_ids = Group::search(['name', '=', 'users'])->ids();
        if(empty($groups_ids)) {
            trigger_error(
                "APP::No Group named 'users' was found while creating User accounts.",
                EQ_REPORT_WARNING
            );
        }

        $current_user_id = $auth->userId();
        $auth->su();

        $created_user_ids = [];
        try {
            foreach($self as $employee_id => $employee) {
                $identity = $employee['identity_id'];
                if(!$identity) {
                    trigger_error(
                        "APP::Ignored User creation for Employee [{$employee_id}] without a related Identity.",
                        EQ_REPORT_WARNING
                    );
                    continue;
                }
                if($identity['user_id']) {
                    trigger_error(
                        "APP::Ignored User creation for Employee [{$employee_id}]: Identity [{$identity['id']}] is already linked to User [{$identity['user_id']}].",
                        EQ_REPORT_INFO
                    );
                    continue;
                }
                if(!$identity['email']) {
                    trigger_error(
                        "APP::Ignored User creation for Employee [{$employee_id}]: Identity [{$identity['id']}] has no email address.",
                        EQ_REPORT_WARNING
                    );
                    continue;
                }

                trigger_error(
                    "APP::Creating a User for Employee [{$employee_id}] and Identity [{$identity['id']}].",
                    EQ_REPORT_DEBUG
                );

                $new_user_id = $orm->create(User::getType(), [
                    // Capabilities for EQ_R_UPDATE are based on the creator context.
                    'creator'       => $current_user_id,
                    'login'         => $identity['email'],
                    'language'      => 'fr',
                    'validated'     => true,
                    'groups_ids'    => $groups_ids
                ]);

                if($new_user_id <= 0) {
                    trigger_error(
                        "APP::Error [{$new_user_id}] while creating a User for Employee [{$employee_id}] and Identity [{$identity['id']}]: {$orm->getLastError()}.",
                        EQ_REPORT_WARNING
                    );
                    continue;
                }

                User::id($new_user_id)
                    ->update(['identity_id' => $identity['id']])
                    ->do('sync_from_identity');

                $created_user_ids[] = $new_user_id;
                trigger_error(
                    "APP::Created User [{$new_user_id}] for Employee [{$employee_id}] and Identity [{$identity['id']}].",
                    EQ_REPORT_INFO
                );
            }
        }
        finally {
            $auth->su($current_user_id);
        }

        trigger_error(
            'APP::Completed User account creation for Employee IDs ['.implode(', ', $loaded_employee_ids)
                .']; created User IDs ['.implode(', ', $created_user_ids).'].',
            EQ_REPORT_DEBUG
        );
    }

    protected static function doDisableUserAccount($self, $auth) {
        $self->read(['identity_id' => ['user_id']]);

        $users_ids = [];
        foreach($self as $employee) {
            $user_id = $employee['identity_id']['user_id'] ?? null;
            if($user_id) {
                $users_ids[] = $user_id;
            }
        }

        if(empty($users_ids)) {
            return;
        }

        $current_user_id = $auth->userId();
        $auth->su();
        try {
            User::ids(array_values(array_unique($users_ids)))->do('suspend');
        }
        finally {
            $auth->su($current_user_id);
        }
    }

    protected static function doDeleteUserAccount($self, $auth) {
        $self->read(['identity_id' => ['id', 'user_id']]);

        $users_ids = [];
        $identities_ids = [];
        foreach($self as $employee) {
            $identity = $employee['identity_id'];
            if(!$identity || !$identity['user_id']) {
                continue;
            }
            $users_ids[] = $identity['user_id'];
            $identities_ids[] = $identity['id'];
        }

        if(empty($users_ids)) {
            return;
        }

        $current_user_id = $auth->userId();
        $auth->su();
        try {
            User::ids(array_values(array_unique($users_ids)))->delete(true);
            Identity::ids(array_values(array_unique($identities_ids)))->update(['user_id' => null]);
        }
        finally {
            $auth->su($current_user_id);
        }
    }
}
