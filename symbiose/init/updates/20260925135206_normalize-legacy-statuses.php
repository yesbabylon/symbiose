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

// Follow-up migration: replay every status rename with a new update timestamp.
// TimeEntry and SubscriptionEntry use single-table inheritance through sale_saleentry.
$status_mappings = [
    'sale_saleentry' => [
        'ready'     => 'submitted',
        'validated' => 'approved',
        'billed'    => 'charged'
    ],
    'sale_receivable_receivable' => [
        'pending' => 'open',
        'posted'  => 'settled'
    ],
    'sale_accounting_invoice_invoice' => [
        'invoice' => 'posted'
    ],
    'finance_accounting_invoice_invoice' => [
        'invoice' => 'posted'
    ],
    'finance_accounting_accountingentry' => [
        'validated' => 'posted',
        'cancelled' => 'reversed'
    ]
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

$db->sendQuery('START TRANSACTION;');

try {
    foreach($status_mappings as $table => $mapping) {
        // Missing tables are expected on installations that do not enable every package.
        if(!isset($existing_tables[$table])) {
            continue;
        }

        $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
        if(!isset($columns['status'])) {
            throw new Exception("Missing required column '{$table}.status'.", EQ_ERROR_INVALID_CONFIG);
        }

        $cases = [];
        $source_statuses = [];

        foreach($mapping as $source_status => $target_status) {
            $cases[] = "WHEN '{$source_status}' THEN '{$target_status}'";
            $source_statuses[] = "'{$source_status}'";
        }

        $db->sendQuery(
            "UPDATE `{$table}`
             SET `status` = CASE `status` ".implode(' ', $cases)." ELSE `status` END
             WHERE `status` IN (".implode(', ', $source_statuses).");"
        );
    }

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
