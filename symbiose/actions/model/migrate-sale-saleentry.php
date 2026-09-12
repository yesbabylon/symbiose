<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Copies the legacy sale entry class discriminator to the technical model column.',
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

$table = 'sale_saleentry';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
if(!isset($columns['model'])) {
    throw new Exception("Missing required column '{$table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

$fetch_count = static function(string $query) use ($db): int {
    $result = $db->sendQuery($query);
    $row = $db->fetchArray($result);
    return (int) ($row['row_count'] ?? 0);
};

if(isset($columns['object_class'])) {
    $invalid_sale_entries = $fetch_count(
        "SELECT COUNT(*) AS row_count
         FROM `{$table}`
         WHERE `object_class` IS NULL OR `object_class` = '';"
    );
    if($invalid_sale_entries > 0) {
        throw new Exception('missing_sale_entry_object_class', EQ_ERROR_CONFLICT_OBJECT);
    }

    $db->sendQuery('START TRANSACTION;');

    try {
        $db->sendQuery("UPDATE `{$table}` SET `model` = `object_class`;");
        $db->sendQuery('COMMIT;');
    }
    catch(Throwable $throwable) {
        $db->sendQuery('ROLLBACK;');
        throw $throwable;
    }
}
else {
    $invalid_sale_entries = $fetch_count(
        "SELECT COUNT(*) AS row_count
         FROM `{$table}`
         WHERE `model` IS NULL OR `model` = '';"
    );
    if($invalid_sale_entries > 0) {
        throw new Exception('missing_sale_entry_model', EQ_ERROR_CONFLICT_OBJECT);
    }
}

$context->httpResponse()
    ->status(204)
    ->send();
