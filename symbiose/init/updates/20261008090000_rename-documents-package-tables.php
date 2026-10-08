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

$table_renames = [
    'document_documentcategory'          => ['documents_documentcategory'],
    'document_document_document'         => ['documents_document_document', 'documents_document'],
    'document_document_documentsubtype'  => ['documents_document_documentsubtype'],
    'document_document_documenttag'      => ['documents_document_documenttag', 'documents_documenttag'],
    'document_document_documenttype'     => ['documents_document_documenttype'],
    'document_navigation_node'           => ['documents_navigation_node'],
    'document_rel_document_tag'          => ['documents_rel_document_tag']
];

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);

$get_row_count = static function($db, string $table): int {
    $result = $db->sendQuery("SELECT COUNT(*) AS row_count FROM `{$table}`;");
    $row = $db->fetchArray($result);

    if(!is_array($row) || !array_key_exists('row_count', $row)) {
        throw new Exception("Unable to count rows in table '{$table}'.", EQ_ERROR_UNKNOWN);
    }

    return (int) $row['row_count'];
};

foreach($table_renames as $target_table => $source_candidates) {
    $source_tables = array_values(array_filter(
        $source_candidates,
        static fn(string $table): bool => isset($existing_tables[$table])
    ));

    if(empty($source_tables)) {
        continue;
    }

    $source_counts = [];
    foreach($source_tables as $source_table) {
        $source_counts[$source_table] = $get_row_count($db, $source_table);
    }

    $non_empty_sources = array_keys(array_filter(
        $source_counts,
        static fn(int $row_count): bool => $row_count > 0
    ));

    if(count($non_empty_sources) > 1) {
        throw new Exception(
            "Cannot rename tables to '{$target_table}': several legacy tables contain data.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    if(isset($existing_tables[$target_table])) {
        $target_count = $get_row_count($db, $target_table);

        if($target_count > 0) {
            if(!empty($non_empty_sources)) {
                throw new Exception(
                    "Cannot rename table '{$non_empty_sources[0]}': target table '{$target_table}' contains data.",
                    EQ_ERROR_CONFLICT_OBJECT
                );
            }

            foreach($source_tables as $source_table) {
                $db->sendQuery("DROP TABLE `{$source_table}`;");
                unset($existing_tables[$source_table]);
            }
            continue;
        }

        $db->sendQuery("DROP TABLE `{$target_table}`;");
        unset($existing_tables[$target_table]);
    }

    $source_table = $non_empty_sources[0] ?? $source_tables[0];
    $db->sendQuery("RENAME TABLE `{$source_table}` TO `{$target_table}`;");

    unset($existing_tables[$source_table]);
    $existing_tables[$target_table] = true;

    foreach($source_tables as $obsolete_table) {
        if($obsolete_table === $source_table) {
            continue;
        }

        $db->sendQuery("DROP TABLE `{$obsolete_table}`;");
        unset($existing_tables[$obsolete_table]);
    }
}

