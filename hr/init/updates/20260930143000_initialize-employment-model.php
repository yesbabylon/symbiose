<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use hr\employee\Employee;
use hr\employee\EmploymentContract;

['db' => $db_connector] = eQual::inject(['db']);

$db = $db_connector->connect();
if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

$today = strtotime(date('Y-m-d'));
$db->sendQuery(
    "UPDATE `hr_employee_contract`
     SET `model` = 'hr\\\\employee\\\\EmploymentContract',
         `status` = CASE
             WHEN `date_end` IS NOT NULL AND `date_end` < {$today} THEN 'ended'
             ELSE 'active'
         END
     WHERE `model` = 'hr\\\\employee\\\\Contract';"
);

EmploymentContract::search()
    ->do('synchronize_employment_status');

Employee::search()
    ->do('refresh_employment_status');
