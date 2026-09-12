<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Renames the mail table and backfills its model discriminator.',
    'help'        => 'The source and target tables must not coexist, and the mail table must provide the technical model column.',
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

$source_table = 'core_mail';
$target_table = 'core_email_email';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
$source_exists = isset($existing_tables[$source_table]);
$target_exists = isset($existing_tables[$target_table]);

if($source_exists && $target_exists) {
    throw new Exception('mail_table_rename_conflict', EQ_ERROR_CONFLICT_OBJECT);
}

if(!$source_exists && !$target_exists) {
    throw new Exception("Missing required table '{$source_table}' or '{$target_table}'.", EQ_ERROR_INVALID_CONFIG);
}

$mail_table = $source_exists ? $source_table : $target_table;
$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($mail_table)), true);
if(!isset($columns['model'])) {
    throw new Exception("Missing required column '{$mail_table}.model'.", EQ_ERROR_INVALID_CONFIG);
}

$email = "CONVERT(0x" . bin2hex('core\email\Email') . " USING utf8mb4)";

if($source_exists) {
    $db->sendQuery("RENAME TABLE `{$source_table}` TO `{$target_table}`;");
}

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery("UPDATE `{$target_table}` SET `model` = {$email};");
    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}

$context->httpResponse()
    ->status(204)
    ->send();
