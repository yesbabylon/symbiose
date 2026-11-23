<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use document\document\Document;
use exchange\import\DataImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Prepare a data import and expand a ZIP input document into child imports.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'exchange\import\DataImport',
            'required'          => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json',
        'charset'       => 'utf-8'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
['context' => $context] = $providers;

$base_import = DataImport::id($params['id'])
    ->read(['id', 'model', 'preparation_status'])
    ->first();

if(!$base_import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

$class = $base_import['model'];
if(!is_string($class) || !class_exists($class) || !is_a($class, DataImport::class, true)) {
    throw new Exception('invalid_data_import_model', EQ_ERROR_INVALID_CONFIG);
}

$send_result = static function() use($context, $class, $params): void {
    $result = $class::id($params['id'])
        ->read(['id', 'status', 'preparation_status', 'has_errors', 'child_imports_ids'])
        ->first(true);

    $context
        ->httpResponse()
        ->body($result)
        ->send();
};

if($base_import['preparation_status'] !== 'pending') {
    $send_result();
    return;
}

$columns = $class::getColumns();
$context_field_candidates = [
    'organization_id',
    'import_type',
    'document_type_id',
    'document_subtype_id',
    'document_visibility'
];
$context_fields = array_values(array_filter(
    $context_field_candidates,
    static fn(string $field): bool => isset($columns[$field])
));

$read_fields = array_merge([
    'id',
    'name',
    'status',
    'preparation_status',
    'has_errors'
], $context_fields);
$read_fields['document_id'] = ['id', 'name'];

$import = $class::id($params['id'])
    ->read($read_fields)
    ->first();

$mark_failing = static function(string $log_key) use($class, $params): void {
    $collection = $class::id($params['id']);
    $collection->write([
        'preparation_status' => 'failing',
        'has_errors'         => true,
        'logs'               => json_encode([$log_key], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    ]);
    $collection->transition('mark_failing');
};

if(!$import || !$import['document_id']) {
    $mark_failing('ERR - missing_import_document');
    $send_result();
    return;
}

$extension = strtolower(pathinfo((string) $import['document_id']['name'], PATHINFO_EXTENSION));
$supported_extensions = array_values(array_unique(array_map(
    static fn(string $supported_extension): string => strtolower(ltrim($supported_extension, '.')),
    $class::SUPPORTED_EXTENSIONS
)));

if($extension !== 'zip') {
    if(count($supported_extensions) > 0 && !in_array($extension, $supported_extensions, true)) {
        $mark_failing('ERR - unsupported_import_file');
        $send_result();
        return;
    }

    $class::id($params['id'])->write(['preparation_status' => 'prepared']);
    $class::id($params['id'])->do('process');

    $processed_import = $class::id($params['id'])
        ->read(['status'])
        ->first();
    if($processed_import && $processed_import['status'] === 'failing') {
        $class::id($params['id'])->write(['has_errors' => true]);
    }

    $send_result();
    return;
}

$class::id($params['id'])->write(['preparation_status' => 'processing']);

try {
    $archive = eQual::run('do', 'document_document_Document_extract-archive', [
        'id'                 => $import['document_id']['id'],
        'allowed_extensions' => $supported_extensions
    ]);
}
catch(Throwable $e) {
    trigger_error(
        "APP::unable to extract archive for data import {$params['id']}: {$e->getMessage()}",
        EQ_REPORT_WARNING
    );
    $mark_failing('ERR - archive_preparation_failed');
    $send_result();
    return;
}

$documents = is_array($archive) ? ($archive['documents'] ?? []) : [];
$skipped = is_array($archive) ? ($archive['skipped'] ?? []) : [];
if(count($documents) === 0) {
    $mark_failing('ERR - archive_contains_no_supported_file');
    $send_result();
    return;
}

$child_context = [];
foreach($context_fields as $field) {
    $child_context[$field] = $import[$field] ?? null;
}

$child_import_ids = [];
$derived_document_ids = array_values(array_filter(array_map(
    static fn(array $document): ?int => isset($document['id']) ? (int) $document['id'] : null,
    $documents
)));

try {
    foreach($documents as $document) {
        if(!isset($document['id'], $document['name'], $document['source_path'], $document['sequence'])) {
            throw new Exception('invalid_archive_extraction_result', EQ_ERROR_UNKNOWN);
        }

        $child_import = $class::create(array_merge($child_context, [
                'state'             => 'draft',
                'name'              => $document['name'],
                'parent_import_id'  => $params['id'],
                'source_path'       => $document['source_path'],
                'sequence'          => $document['sequence']
            ]))
            ->first();

        if(!$child_import) {
            throw new Exception('child_import_creation_failed', EQ_ERROR_UNKNOWN);
        }

        $child_import_ids[] = $child_import['id'];
        $class::id($child_import['id'])->write(['document_id' => $document['id']]);
    }
}
catch(Throwable $e) {
    if(count($child_import_ids) > 0) {
        $class::ids($child_import_ids)->delete(true);
    }
    if(count($derived_document_ids) > 0) {
        Document::ids($derived_document_ids)->delete(true);
    }

    trigger_error(
        "APP::unable to create children for data import {$params['id']}: {$e->getMessage()}",
        EQ_REPORT_ERROR
    );
    $mark_failing('ERR - child_import_creation_failed');
    $send_result();
    return;
}

$logs = [
    'INFO - archive extracted into ' . count($child_import_ids) . ' import(s)'
];
if(count($skipped) > 0) {
    $logs[] = 'WARN - skipped ' . count($skipped) . ' unsupported or system file(s)';
}

$class::id($params['id'])->write([
    'preparation_status' => 'prepared',
    'has_errors'         => count($skipped) > 0,
    'logs'               => json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
]);

try {
    $class::ids($child_import_ids)->update(['state' => 'instance']);
}
catch(Throwable $e) {
    trigger_error(
        "APP::unable to instantiate all children for data import {$params['id']}: {$e->getMessage()}",
        EQ_REPORT_ERROR
    );
    $mark_failing('ERR - child_import_processing_failed');
    $send_result();
    return;
}

$imported_count = 0;
$failed_count = 0;
$children_have_errors = false;
$children = $class::ids($child_import_ids)->read(['status', 'has_errors']);
foreach($children as $child) {
    if($child['status'] === 'imported') {
        ++$imported_count;
    }
    else {
        ++$failed_count;
    }

    if($child['has_errors']) {
        $children_have_errors = true;
    }
}

$has_errors = count($skipped) > 0 || $failed_count > 0 || $children_have_errors;
$logs[] = "INFO - imported {$imported_count} child import(s)";
if($failed_count > 0) {
    $logs[] = "WARN - {$failed_count} child import(s) failed";
}

$class::id($params['id'])->write([
    'has_errors' => $has_errors,
    'logs'       => json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
]);

if($imported_count > 0) {
    $class::id($params['id'])
        ->transition('mark_ready')
        ->transition('mark_imported');
}
else {
    $class::id($params['id'])->transition('mark_failing');
}

$send_result();
