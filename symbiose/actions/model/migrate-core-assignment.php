<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Backfills the model discriminator of assignments.',
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

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables['core_assignment'])) {
    throw new Exception("Missing required table 'core_assignment'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns('core_assignment')), true);
foreach(['model', 'object_class', 'role'] as $column) {
    if(!isset($columns[$column])) {
        throw new Exception("Missing required column 'core_assignment.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$sql_literal = static function(string $value): string {
    return "CONVERT(0x" . bin2hex($value) . " USING utf8mb4)";
};

$document = $sql_literal('documents\Document');
$document_role_assignment = $sql_literal('documents\DocumentRoleAssignment');
$assignment = $sql_literal('core\Assignment');
$owner = $sql_literal('owner');
$editor = $sql_literal('editor');
$viewer = $sql_literal('viewer');

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `core_assignment`
         SET `model` = CASE
            WHEN `object_class` = {$document} AND `role` IN ({$owner}, {$editor}, {$viewer})
                THEN {$document_role_assignment}
            ELSE {$assignment}
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
