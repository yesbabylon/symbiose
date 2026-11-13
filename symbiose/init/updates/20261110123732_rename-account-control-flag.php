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

$table = 'finance_accounting_account';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

if(!isset($existing_tables[$table])) {
    return;
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);

if(!isset($columns['is_group_account'])) {
    return;
}

if(!isset($columns['is_control_account'])) {
    throw new Exception('missing_target_column', EQ_ERROR_INVALID_CONFIG);
}

$db->sendQuery(
    "UPDATE `{$table}` SET `is_control_account` = `is_group_account`;"
);

$db->sendQuery(
    "ALTER TABLE `{$table}` DROP COLUMN `is_group_account`;"
);
