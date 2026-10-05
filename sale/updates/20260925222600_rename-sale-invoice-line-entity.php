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

$table = 'sale_accounting_invoice_invoiceline';
$old_class = 'sale\accounting\invoice\InvoiceLine';
$new_class = 'sale\accounting\invoice\SaleInvoiceLine';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

if(!isset($existing_tables[$table])) {
    return;
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
if(!isset($columns['model'])) {
    throw new Exception("Missing required column '{$table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

$db->sendQuery('START TRANSACTION;');

try {
    $db->setRecords(
        $table,
        [],
        ['model' => $new_class],
        [[['model', '=', $old_class]]]
    );

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
