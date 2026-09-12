<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Backfills access models from their infrastructure or inventory targets.',
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

$table = 'infra_access';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
foreach(['model', 'server_id', 'instance_id', 'software_id', 'service_id'] as $column) {
    if(!isset($columns[$column])) {
        throw new Exception("Missing required column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$fetch_count = static function(string $query) use ($db): int {
    $result = $db->sendQuery($query);
    $row = $db->fetchArray($result);
    return (int) ($row['row_count'] ?? 0);
};

$mixed_accesses = $fetch_count(
    "SELECT COUNT(*) AS row_count
     FROM `{$table}`
     WHERE (`server_id` IS NOT NULL OR `instance_id` IS NOT NULL)
       AND (`software_id` IS NOT NULL OR `service_id` IS NOT NULL);"
);
if($mixed_accesses > 0) {
    throw new Exception('mixed_access_targets', EQ_ERROR_CONFLICT_OBJECT);
}

$inventory_access = "CONVERT(0x" . bin2hex('inventory\Access') . " USING utf8mb4)";
$infra_access = "CONVERT(0x" . bin2hex('infra\Access') . " USING utf8mb4)";

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$table}`
         SET `model` = CASE
            WHEN `software_id` IS NOT NULL OR `service_id` IS NOT NULL THEN {$inventory_access}
            ELSE {$infra_access}
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
