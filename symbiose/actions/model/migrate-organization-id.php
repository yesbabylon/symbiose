<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Renames organization relation columns and the related identity model discriminator.',
    'help'        => 'The migration is idempotent and must run after deploying the renamed model fields, before package initialization.',
    'params'      => [
        'confirm' => [
            'description' => 'Explicit confirmation that the database migration may run.',
            'type'        => 'boolean',
            'required'    => true
        ]
    ],
    'access'      => ['visibility' => 'private'],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'db']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\db\DBConnector $db_connector
 */
['context' => $context, 'db' => $db_connector] = $providers;

if(!$params['confirm']) {
    throw new Exception('migration_not_confirmed', EQ_ERROR_NOT_ALLOWED);
}

$db = $db_connector->connect();
if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

$tables = [
    'communication_conversation_channel',
    'core_setting_settingsequence',
    'core_setting_settingvalue',
    'core_user',
    'finance_accounting_account_chart_template',
    'finance_accounting_accountchart',
    'finance_accounting_accountingjournal',
    'finance_accounting_analyticchart',
    'finance_accounting_invoice_invoice',
    'finance_accounting_operation_accountingoperationline',
    'finance_bank_bankaccount',
    'finance_stats_statchart',
    'hr_absence_absence',
    'hr_employee_employee',
    'identity_contact',
    'identity_establishment',
    'identity_identity',
    'identity_organisation',
    'identity_partner',
    'purchase_accounting_invoice_invoice',
    'purchase_supplier_supplier',
    'sale_accounting_invoice_invoice',
    'sale_customer_contact',
    'sale_customer_customer',
    'sale_terms_terms',
    'talentlead_identity_talent'
];

$source_column = 'organisation_id';
$target_column = 'organization_id';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
$table_columns = [];
$column_definitions = [];
$tables_to_migrate = [];

foreach($tables as $table) {
    if(!isset($existing_tables[$table])) {
        throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
    }

    $columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
    $table_columns[$table] = $columns;
    $has_source = isset($columns[$source_column]);
    $has_target = isset($columns[$target_column]);

    if($has_source && $has_target) {
        throw new Exception(
            "Ambiguous columns on '{$table}': both '{$source_column}' and '{$target_column}' exist.",
            EQ_ERROR_INVALID_CONFIG
        );
    }

    if(!$has_source && !$has_target) {
        throw new Exception(
            "Missing required column '{$table}.{$source_column}' or '{$table}.{$target_column}'.",
            EQ_ERROR_INVALID_CONFIG
        );
    }

    if($has_source) {
        $result = $db->sendQuery("SHOW CREATE TABLE `{$table}`;");
        $row = $db->fetchRow($result);
        $definition = null;

        foreach(preg_split('/\R/', $row[1]) as $line) {
            $line = trim($line);
            if(preg_match('/^`' . preg_quote($source_column, '/') . '`\s+(.+)$/', $line, $matches)) {
                $definition = rtrim($matches[1], ',');
                break;
            }
        }

        if($definition === null) {
            throw new Exception(
                "Unable to read the definition of '{$table}.{$source_column}'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }

        $column_definitions[$table] = $definition;
        $tables_to_migrate[] = $table;
    }
}

foreach($tables_to_migrate as $table) {
    $db->sendQuery(
        "ALTER TABLE `{$table}` CHANGE COLUMN `{$source_column}` `{$target_column}` {$column_definitions[$table]};"
    );
}

if(isset($table_columns['identity_organisation']['model'])) {
    $source_model = "CONVERT(0x" . bin2hex('identity\Organisation') . " USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    $target_model = "CONVERT(0x" . bin2hex('identity\Organization') . " USING utf8mb4) COLLATE utf8mb4_unicode_ci";

    $db->sendQuery(
        "UPDATE `identity_organisation`
         SET `model` = {$target_model}
         WHERE `model` = {$source_model};"
    );
}

$context->httpResponse()
    ->status(204)
    ->send();
