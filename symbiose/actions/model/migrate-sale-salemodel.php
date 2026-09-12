<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Replaces the deprecated time entry sale model discriminator.',
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

$table = 'sale_salemodel';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
if(!isset($columns['model'])) {
    throw new Exception("Missing required column '{$table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

$sale_model = "CONVERT(0x" . bin2hex('sale\SaleModel') . " USING utf8mb4) COLLATE utf8mb4_unicode_ci";
$time_entry_sale_model = "CONVERT(0x" . bin2hex('timetrack\TimeEntrySaleModel') . " USING utf8mb4) COLLATE utf8mb4_unicode_ci";

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$table}`
         SET `model` = {$sale_model}
         WHERE `model` IS NULL OR `model` = '' OR `model` = {$time_entry_sale_model};"
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
