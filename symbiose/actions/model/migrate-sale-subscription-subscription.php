<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Backfills subscription models from service references.',
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

$table = 'sale_subscription_subscription';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
foreach(['model', 'service_id'] as $column) {
    if(!isset($columns[$column])) {
        throw new Exception("Missing required column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$infra_subscription = "CONVERT(0x" . bin2hex('infra\service\Subscription') . " USING utf8mb4)";
$sale_subscription = "CONVERT(0x" . bin2hex('sale\subscription\Subscription') . " USING utf8mb4)";

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$table}`
         SET `model` = CASE
            WHEN `service_id` IS NOT NULL THEN {$infra_subscription}
            ELSE {$sale_subscription}
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
