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

$dbms = strtoupper((string) constant('DB_DBMS'));

if(!in_array($dbms, ['MYSQL', 'MARIADB'], true)) {
    throw new Exception('unsupported_dbms', EQ_ERROR_INVALID_CONFIG);
}

$table_renames = [
    'sale_contract_serviceaccountentry' => 'sale_serviceaccount_serviceaccountentry',
    'sale_contract_report'              => 'sale_serviceaccount_report'
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

foreach($table_renames as $source_table => $target_table) {
    $source_exists = isset($existing_tables[$source_table]);
    $target_exists = isset($existing_tables[$target_table]);

    // The table is already renamed, or neither table exists on a fresh installation.
    if(!$source_exists) {
        continue;
    }

    // init_package might have created the newly expected table before this update is run.
    if($target_exists) {
        $result = $db->sendQuery("SELECT COUNT(*) AS row_count FROM `{$target_table}`;");
        $row = $db->fetchArray($result);

        if((int) ($row['row_count'] ?? 0) > 0) {
            throw new Exception(
                "Cannot rename table '{$source_table}': target table '{$target_table}' contains data.",
                EQ_ERROR_CONFLICT_OBJECT
            );
        }

        $db->sendQuery("DROP TABLE `{$target_table}`;");
    }

    $db->sendQuery("RENAME TABLE `{$source_table}` TO `{$target_table}`;");

    unset($existing_tables[$source_table]);
    $existing_tables[$target_table] = true;
}
