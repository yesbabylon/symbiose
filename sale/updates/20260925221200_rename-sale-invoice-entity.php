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

$old_class = 'sale\accounting\invoice\Invoice';
$new_class = 'sale\accounting\invoice\SaleInvoice';

$class_columns = [
    'sale_accounting_invoice_invoice' => 'model',
    'finance_accounting_accountingentry' => 'origin_object_class'
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

$db->sendQuery('START TRANSACTION;');

try {
    foreach($class_columns as $table => $column) {
        // Missing tables are expected on installations that do not enable every package.
        if(!isset($existing_tables[$table])) {
            continue;
        }

        $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
        if(!isset($columns[strtolower($column)])) {
            throw new Exception("Missing required column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
        }

        $db->setRecords(
            $table,
            [],
            [$column => $new_class],
            [[[$column, '=', $old_class]]]
        );
    }

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
