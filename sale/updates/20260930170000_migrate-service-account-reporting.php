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

$tables = [
    'account'    => 'sale_serviceaccount_serviceaccount',
    'entry'      => 'sale_serviceaccount_serviceaccountentry',
    'report'     => 'sale_serviceaccount_report',
    'receivable' => 'sale_receivable_receivable'
];
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
foreach($tables as $table) {
    if(!isset($existing_tables[$table])) {
        // Fresh installations may not have materialized the model tables yet.
        return;
    }
}

$required_columns = [
    $tables['account']    => ['m_reporting', 'reporting_mode', 'reporting_frequency', 'balance_current', 'balance_effective', 'balance_estimated'],
    $tables['entry']      => ['receivable_id', 'service_account_id', 'report_id', 'status'],
    $tables['report']     => ['service_account_id', 'status', 'balance_new'],
    $tables['receivable'] => ['service_account_id', 'service_account_entry_id']
];
foreach($required_columns as $table => $required) {
    $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
    foreach($required as $column) {
        if(!isset($columns[$column])) {
            throw new Exception("Service account migration requires column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
        }
    }
}

$first_row = function(string $sql) use($db) {
    $result = $db->sendQuery($sql);
    return $db->fetchArray($result) ?: null;
};

$db->sendQuery('START TRANSACTION;');
try {
    $duplicate = $first_row(
        "SELECT service_account_entry_id, COUNT(*) AS relation_count
         FROM `{$tables['receivable']}`
         WHERE service_account_entry_id IS NOT NULL AND service_account_entry_id > 0
         GROUP BY service_account_entry_id
         HAVING COUNT(*) > 1
         LIMIT 1;"
    );
    if($duplicate) {
        throw new Exception(
            "Service account migration stopped: entry {$duplicate['service_account_entry_id']} is linked to several receivables.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $asymmetric = $first_row(
        "SELECT e.id AS entry_id, e.receivable_id, r.id AS inverse_receivable_id
         FROM `{$tables['entry']}` e
         JOIN `{$tables['receivable']}` r ON r.service_account_entry_id = e.id
         WHERE e.receivable_id IS NOT NULL
           AND e.receivable_id > 0
           AND e.receivable_id <> r.id
         LIMIT 1;"
    );
    if($asymmetric) {
        throw new Exception(
            "Service account migration stopped: entry {$asymmetric['entry_id']} has asymmetric receivable relations.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $db->sendQuery(
        "UPDATE `{$tables['entry']}` e
         JOIN `{$tables['receivable']}` r ON r.service_account_entry_id = e.id
         SET e.receivable_id = r.id
         WHERE e.receivable_id IS NULL OR e.receivable_id = 0;"
    );

    $orphan = $first_row(
        "SELECT id FROM `{$tables['entry']}`
         WHERE receivable_id IS NULL OR receivable_id = 0
         LIMIT 1;"
    );
    if($orphan) {
        trigger_error(
            "APP::Service account migration ignored entry {$orphan['id']} with no receivable.",
            EQ_REPORT_WARNING
        );
    }

    $inverse_mismatch = $first_row(
        "SELECT e.id AS entry_id, e.receivable_id, r.service_account_entry_id
         FROM `{$tables['entry']}` e
         JOIN `{$tables['receivable']}` r ON r.id = e.receivable_id
         WHERE r.service_account_entry_id IS NULL
            OR r.service_account_entry_id <> e.id
         LIMIT 1;"
    );
    if($inverse_mismatch) {
        throw new Exception(
            "Service account migration stopped: entry {$inverse_mismatch['entry_id']} and its receivable are not symmetric.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $multiple_pending = $first_row(
        "SELECT service_account_id, COUNT(*) AS report_count
         FROM `{$tables['report']}`
         WHERE status = 'pending'
         GROUP BY service_account_id
         HAVING COUNT(*) > 1
         LIMIT 1;"
    );
    if($multiple_pending) {
        throw new Exception(
            "Service account migration stopped: account {$multiple_pending['service_account_id']} has several pending reports.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $account_mismatch = $first_row(
        "SELECT e.id AS entry_id
         FROM `{$tables['entry']}` e
         JOIN `{$tables['report']}` rp ON rp.id = e.report_id
         WHERE e.service_account_id <> rp.service_account_id
         LIMIT 1;"
    );
    if($account_mismatch) {
        throw new Exception(
            "Service account migration stopped: entry {$account_mismatch['entry_id']} belongs to another account than its report.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $receivable_account_mismatch = $first_row(
        "SELECT e.id AS entry_id
         FROM `{$tables['entry']}` e
         JOIN `{$tables['receivable']}` r ON r.id = e.receivable_id
         WHERE r.service_account_id IS NOT NULL
           AND r.service_account_id > 0
           AND e.service_account_id <> r.service_account_id
         LIMIT 1;"
    );
    if($receivable_account_mismatch) {
        throw new Exception(
            "Service account migration stopped: entry {$receivable_account_mismatch['entry_id']} belongs to another account than its receivable.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    $db->sendQuery(
        "UPDATE `{$tables['account']}`
         SET reporting_mode = COALESCE(NULLIF(m_reporting, ''), 'send'),
             reporting_frequency = COALESCE(NULLIF(reporting_frequency, ''), 'monthly'),
             balance_effective = COALESCE(balance_current, 0),
             balance_estimated = COALESCE(balance_current, 0);"
    );

    $db->sendQuery(
        "UPDATE `{$tables['entry']}` e
         LEFT JOIN `{$tables['report']}` rp ON rp.id = e.report_id
         SET e.status = CASE
             WHEN rp.id IS NOT NULL AND rp.status <> 'pending' THEN 'released'
             ELSE 'pending'
         END;"
    );

    $db->sendQuery(
        "UPDATE `{$tables['account']}` a
         JOIN `{$tables['report']}` rp
           ON rp.service_account_id = a.id AND rp.status = 'pending'
         SET a.balance_estimated = COALESCE(rp.balance_new, a.balance_effective);"
    );

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
