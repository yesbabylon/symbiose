<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Backfills authentication factor models from their mechanism type.',
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

$table = 'core_security_authenticationfactor';
$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$table])) {
    throw new Exception("Missing required table '{$table}'.", EQ_ERROR_INVALID_CONFIG);
}

$columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($table)), true);
foreach(['model', 'type'] as $column) {
    if(!isset($columns[$column])) {
        throw new Exception("Missing required column '{$table}.{$column}'.", EQ_ERROR_INVALID_CONFIG);
    }
}

$sql_literal = static function(string $value): string {
    return "CONVERT(0x" . bin2hex($value) . " USING utf8mb4)";
};

$fetch_count = static function(string $query) use ($db): int {
    $result = $db->sendQuery($query);
    $row = $db->fetchArray($result);
    return (int) ($row['row_count'] ?? 0);
};

$authentication_factor = $sql_literal('core\security\AuthenticationFactor');
$passkey = $sql_literal('core\security\factor\Passkey');
$totp_key = $sql_literal('core\security\factor\TotpKey');
$recovery_code_type = $sql_literal('recovery_code');
$passkey_type = $sql_literal('passkey');
$totp_type = $sql_literal('totp');

$unexpected_factor_types = $fetch_count(
    "SELECT COUNT(*) AS row_count
     FROM `{$table}`
     WHERE `type` IS NULL OR `type` NOT IN ({$passkey_type}, {$totp_type}, {$recovery_code_type});"
);
if($unexpected_factor_types > 0) {
    throw new Exception('unsupported_authentication_factor_type', EQ_ERROR_CONFLICT_OBJECT);
}

$db->sendQuery('START TRANSACTION;');

try {
    $db->sendQuery(
        "UPDATE `{$table}`
         SET `model` = CASE `type`
            WHEN {$passkey_type} THEN {$passkey}
            WHEN {$totp_type} THEN {$totp_key}
            ELSE {$authentication_factor}
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
