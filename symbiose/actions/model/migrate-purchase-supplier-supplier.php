<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Backfills supplier models from service provider references.',
    'help'        => 'The target environment must already provide the technical model column.',
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

$table = 'purchase_supplier_supplier';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
foreach(['model', 'service_provider_category_id'] as $column) {
    if(!isset($columns[$column])) {
        throw new Exception("Missing required column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$sql_literal = static function(string $value): string {
    return "CONVERT(0x" . bin2hex($value) . " USING utf8mb4) COLLATE utf8mb4_unicode_ci";
};

$service_provider_references = [];
$service_provider_column = $sql_literal('service_provider_id');
$reference_tables_result = $db->sendQuery(
    "SELECT column_info.`TABLE_NAME`
     FROM `information_schema`.`COLUMNS` column_info
     INNER JOIN `information_schema`.`TABLES` table_info
        ON table_info.`TABLE_SCHEMA` = column_info.`TABLE_SCHEMA`
       AND table_info.`TABLE_NAME` = column_info.`TABLE_NAME`
     WHERE column_info.`TABLE_SCHEMA` = DATABASE()
       AND column_info.`COLUMN_NAME` = {$service_provider_column}
       AND table_info.`TABLE_TYPE` = 'BASE TABLE';"
);
while($row = $db->fetchArray($reference_tables_result)) {
    $reference_table = (string) ($row['TABLE_NAME'] ?? '');
    if($reference_table === '' || $reference_table === $table) {
        continue;
    }
    $service_provider_references[] =
        "EXISTS (SELECT 1 FROM `{$reference_table}` provider_reference WHERE provider_reference.`service_provider_id` = supplier.`id`)";
}

$service_provider_conditions = array_merge(
    ['supplier.`service_provider_category_id` IS NOT NULL'],
    $service_provider_references
);
$service_provider = $sql_literal('inventory\service\ServiceProvider');
$supplier = $sql_literal('purchase\supplier\Supplier');

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$table}` supplier
         SET `model` = CASE
            WHEN " . implode(' OR ', $service_provider_conditions) . " THEN {$service_provider}
            ELSE {$supplier}
         END;"
    );
    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}

$context->httpResponse()
    ->status(204)
    ->send();
