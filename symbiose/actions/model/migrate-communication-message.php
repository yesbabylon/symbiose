<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Moves conversation message rows to their dedicated table.',
    'help'        => 'The target environment must already provide the conversation message table.',
    'params'      => [
        'confirm' => [
            'description' => 'Explicit confirmation that the database migration may run.',
            'type'        => 'boolean',
            'required'    => true
        ]
    ],
    'access'      => [
        'visibility' => 'private'
    ],
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

$sql_literal = static function(string $value): string {
    return "CONVERT(0x" . bin2hex($value) . " USING utf8mb4)";
};

$fetch_count = static function(string $query) use ($db): int {
    $result = $db->sendQuery($query);
    $row = $db->fetchArray($result);
    return (int) ($row['row_count'] ?? 0);
};

$source_table = 'communication_message';
$target_table = 'communication_conversation_conversationmessage';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

if(!isset($existing_tables[$source_table])) {
    $context->httpResponse()
        ->status(204)
        ->send();
    return;
}

if(!isset($existing_tables[$target_table])) {
    throw new Exception("Missing required table '{$target_table}'.", EQ_ERROR_INVALID_CONFIG);
}

$source_columns = array_map('strtolower', $db->getTableColumns($source_table));
$target_columns = array_fill_keys(
    array_map('strtolower', $db->getTableColumns($target_table)),
    true
);

if(!in_array('conversation_id', $source_columns, true)) {
    throw new Exception('missing_conversation_id', EQ_ERROR_INVALID_CONFIG);
}

if(!isset($target_columns['model'])) {
    throw new Exception("Missing required column '{$target_table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

foreach($source_columns as $column) {
    if(!isset($target_columns[$column])) {
        throw new Exception("Missing target column '{$target_table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$invalid_conversations = $fetch_count(
    "SELECT COUNT(*) AS row_count FROM `{$source_table}` WHERE `conversation_id` IS NULL;"
);
if($invalid_conversations > 0) {
    throw new Exception('conversation_message_without_conversation', EQ_ERROR_CONFLICT_OBJECT);
}

$comparable_columns = array_values(array_diff($source_columns, ['id', 'model']));
$difference_conditions = array_map(
    static fn(string $column): string => "NOT (target.`{$column}` <=> source.`{$column}`)",
    $comparable_columns
);

if(count($difference_conditions)) {
    $conflicts = $fetch_count(
        "SELECT COUNT(*) AS row_count
         FROM `{$source_table}` source
         INNER JOIN `{$target_table}` target ON target.`id` = source.`id`
         WHERE " . implode(' OR ', $difference_conditions) . ";"
    );
    if($conflicts > 0) {
        throw new Exception('conversation_message_id_conflict', EQ_ERROR_CONFLICT_OBJECT);
    }
}

$conversation_message = $sql_literal('communication\conversation\ConversationMessage');
$insert_columns = $source_columns;
$select_expressions = array_map(
    static fn(string $column): string => "source.`{$column}`",
    $source_columns
);

if(!in_array('model', $source_columns, true)) {
    $insert_columns[] = 'model';
    $select_expressions[] = $conversation_message;
}

$quoted_columns = array_map(
    static fn(string $column): string => "`{$column}`",
    $insert_columns
);
$columns_sql = implode(', ', $quoted_columns);
$source_columns_sql = implode(', ', $select_expressions);

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "INSERT INTO `{$target_table}` ({$columns_sql})
         SELECT {$source_columns_sql}
         FROM `{$source_table}` source
         WHERE NOT EXISTS (
            SELECT 1 FROM `{$target_table}` target WHERE target.`id` = source.`id`
         );"
    );

    $remaining_rows = $fetch_count(
        "SELECT COUNT(*) AS row_count
         FROM `{$source_table}` source
         LEFT JOIN `{$target_table}` target ON target.`id` = source.`id`
         WHERE target.`id` IS NULL;"
    );
    if($remaining_rows > 0) {
        throw new Exception('conversation_message_copy_incomplete', EQ_ERROR_UNKNOWN);
    }

    $db->sendQuery(
        "UPDATE `{$target_table}` SET `model` = {$conversation_message};"
    );
    $db->sendQuery("DELETE FROM `{$source_table}`;");

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}

$context->httpResponse()
    ->status(204)
    ->send();
