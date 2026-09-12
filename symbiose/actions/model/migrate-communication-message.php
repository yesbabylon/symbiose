<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Renames the conversation message table and updates its model discriminator.',
    'help'        => 'An existing target table is replaced only when it contains no rows.',
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

$source_table = 'communication_message';
$target_table = 'communication_conversation_conversationmessage';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
$source_exists = isset($existing_tables[$source_table]);
$target_exists = isset($existing_tables[$target_table]);

if(!$source_exists) {
    $context->httpResponse()
        ->status(204)
        ->send();
    return;
}

$source_columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($source_table)), true);

if(!isset($source_columns['conversation_id'])) {
    throw new Exception('missing_conversation_id', EQ_ERROR_INVALID_CONFIG);
}

if(!isset($source_columns['model'])) {
    throw new Exception("Missing required column '{$source_table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

if($target_exists) {
    $result = $db->sendQuery("SELECT 1 FROM `{$target_table}` LIMIT 1;");
    if($db->fetchArray($result)) {
        throw new Exception('conversation_message_table_rename_conflict', EQ_ERROR_CONFLICT_OBJECT);
    }
    $db->sendQuery("DROP TABLE `{$target_table}`;");
}

$conversation_message = "CONVERT(0x" . bin2hex('communication\conversation\ConversationMessage') . " USING utf8mb4)";

$db->sendQuery("RENAME TABLE `{$source_table}` TO `{$target_table}`;");

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery("UPDATE `{$target_table}` SET `model` = {$conversation_message};");
    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}

$context->httpResponse()
    ->status(204)
    ->send();
