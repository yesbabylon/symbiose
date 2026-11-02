<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use hr\employee\Employee;
use hr\role\RoleAssignment;

$employees = Employee::search([['role_id', '>', 0]])
    ->read(['role_id']);

foreach($employees as $employee_id => $employee) {
    $assignment = RoleAssignment::search([
            ['employee_id', '=', $employee_id],
            ['role_id', '=', $employee['role_id']]
        ])
        ->first();

    if($assignment) {
        RoleAssignment::id($assignment['id'])
            ->update(['is_primary' => true]);
        continue;
    }

    RoleAssignment::create([
        'employee_id' => $employee_id,
        'role_id'     => $employee['role_id'],
        'is_primary'  => true
    ]);
}
