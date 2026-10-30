<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

['db' => $db_connector] = eQual::inject(['db']);

$db = $db_connector->connect();

if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

if(!$db instanceof \equal\db\DBManipulatorMySQL) {
    throw new Exception('unsupported_dbms', EQ_ERROR_INVALID_CONFIG);
}

$tables = [
    'entry'      => 'sale_serviceaccount_serviceaccountentry',
    'receivable' => 'sale_receivable_receivable'
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
foreach($tables as $table) {
    // Fresh installations may not have materialized every package table yet.
    if(!isset($existing_tables[$table])) {
        return;
    }
}

$required_columns = [
    $tables['entry']      => ['receivable_id', 'employee_id'],
    $tables['receivable'] => ['employee_id']
];

foreach($required_columns as $table => $required) {
    $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
    foreach($required as $column) {
        if(!isset($columns[$column])) {
            throw new Exception(
                "Service account entry employee migration requires column '{$table}.{$column}'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }
    }
}

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$tables['entry']}` e
         JOIN `{$tables['receivable']}` r ON r.id = e.receivable_id
         SET e.employee_id = r.employee_id
         WHERE e.receivable_id IS NOT NULL
           AND e.receivable_id > 0
           AND (e.employee_id IS NULL OR e.employee_id = 0)
           AND r.employee_id IS NOT NULL
           AND r.employee_id > 0;"
    );

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
