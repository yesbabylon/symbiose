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

$source_table = 'finance_accounting_accountingjournal';
$target_table = 'finance_accounting_journal';
$old_class = 'finance\accounting\AccountingJournal';
$new_class = 'finance\accounting\Journal';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

$get_row_count = static function($db, string $table): int {
    $result = $db->sendQuery("SELECT COUNT(*) AS row_count FROM `{$table}`;");
    $row = $db->fetchArray($result);

    if(!is_array($row) || !array_key_exists('row_count', $row)) {
        throw new Exception("Unable to count rows in table '{$table}'.", EQ_ERROR_UNKNOWN);
    }

    return (int) $row['row_count'];
};

if(isset($existing_tables[$source_table])) {
    if(isset($existing_tables[$target_table])) {
        $source_count = $get_row_count($db, $source_table);
        $target_count = $get_row_count($db, $target_table);

        if($source_count > 0 && $target_count > 0) {
            throw new Exception(
                "Cannot rename table '{$source_table}': target table '{$target_table}' contains data.",
                EQ_ERROR_CONFLICT_OBJECT
            );
        }

        if($target_count > 0) {
            $db->sendQuery("DROP TABLE `{$source_table}`;");
            unset($existing_tables[$source_table]);
        }
        else {
            $db->sendQuery("DROP TABLE `{$target_table}`;");
            unset($existing_tables[$target_table]);
        }
    }

    if(isset($existing_tables[$source_table])) {
        $db->sendQuery("RENAME TABLE `{$source_table}` TO `{$target_table}`;");
    }
}

$db->sendQuery('START TRANSACTION;');

try {
    foreach($db->getTables() as $table) {
        foreach($db->getTableColumns($table) as $column) {
            $normalized_column = strtolower($column);
            if($normalized_column !== 'model' && substr($normalized_column, -6) !== '_class') {
                continue;
            }

            $db->setRecords(
                $table,
                [],
                [$column => $new_class],
                [[[$column, '=', $old_class]]]
            );
        }
    }

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
