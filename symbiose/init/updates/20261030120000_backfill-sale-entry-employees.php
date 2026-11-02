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
    'sale_entry' => 'sale_saleentry',
    'identity'   => 'identity_identity'
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
foreach($tables as $table) {
    // Fresh installations may not have materialized every package table yet.
    if(!isset($existing_tables[$table])) {
        return;
    }
}

$required_columns = [
    $tables['sale_entry'] => ['user_id', 'employee_id'],
    $tables['identity']   => ['user_id', 'employee_id']
];

foreach($required_columns as $table => $required) {
    $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
    foreach($required as $column) {
        if(!isset($columns[$column])) {
            throw new Exception(
                "Sale entry employee migration requires column '{$table}.{$column}'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }
    }
}

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$tables['sale_entry']}` se
         JOIN `{$tables['identity']}` i ON i.user_id = se.user_id
         SET se.employee_id = i.employee_id
         WHERE se.user_id IS NOT NULL
           AND se.user_id > 0
           AND (se.employee_id IS NULL OR se.employee_id = 0)
           AND i.employee_id IS NOT NULL
           AND i.employee_id > 0;"
    );

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
