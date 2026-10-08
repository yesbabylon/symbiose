<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

['db' => $db_connector] = eQual::inject(['db']);

$db = $db_connector->connect();

if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

$dbms = strtoupper((string) constant('DB_DBMS'));

if(!in_array($dbms, ['MYSQL', 'MARIADB'], true)) {
    throw new Exception('unsupported_dbms', EQ_ERROR_INVALID_CONFIG);
}

$exact_renames = [
    'documents\Document'    => 'document\document\Document',
    'documents\DocumentTag' => 'document\document\DocumentTag'
];

$old_prefix = 'documents\\';
$new_prefix = 'document\\';

$sql_literal = static function(string $value): string {
    return "CONVERT(0x".bin2hex($value)." USING utf8mb4)";
};

$old_prefix_literal = $sql_literal($old_prefix);
$new_prefix_literal = $sql_literal($new_prefix);
$old_prefix_length = strlen($old_prefix) + 1;

$db->sendQuery('START TRANSACTION;');

try {
    foreach($db->getTables() as $table) {
        foreach($db->getTableColumns($table) as $column) {
            $normalized_column = strtolower($column);
            if($normalized_column !== 'model' && substr($normalized_column, -6) !== '_class') {
                continue;
            }

            foreach($exact_renames as $old_class => $new_class) {
                $db->setRecords(
                    $table,
                    [],
                    [$column => $new_class],
                    [[[$column, '=', $old_class]]]
                );
            }

            $db->sendQuery(
                "UPDATE `{$table}`
                 SET `{$column}` = CONCAT({$new_prefix_literal}, SUBSTRING(`{$column}`, {$old_prefix_length}))
                 WHERE LEFT(`{$column}`, ".strlen($old_prefix).") = {$old_prefix_literal};"
            );
        }
    }

    $db->sendQuery('COMMIT;');
}
catch(Throwable $throwable) {
    $db->sendQuery('ROLLBACK;');
    throw $throwable;
}
