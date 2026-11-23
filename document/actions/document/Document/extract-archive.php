<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use document\document\Document;

[$params, $providers] = eQual::announce([
    'description'   => 'Extract supported files from a ZIP document into derived documents.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'document\document\Document',
            'required'          => true
        ],
        'allowed_extensions' => [
            'type'              => 'array',
            'default'           => [],
            'description'       => 'Lowercase filename extensions to extract. An empty list accepts every non-ZIP file.'
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

$document = Document::id($params['id'])
    ->read([
        'id',
        'name',
        'data',
        'organization_id',
        'document_visibility',
        'document_type_id',
        'document_subtype_id'
    ])
    ->first();

if(!$document) {
    throw new Exception('document_unknown', EQ_ERROR_UNKNOWN_OBJECT);
}

$extension = strtolower(pathinfo((string) $document['name'], PATHINFO_EXTENSION));
if($extension !== 'zip') {
    throw new Exception('document_not_zip_archive', EQ_ERROR_INVALID_PARAM);
}

if(!class_exists(ZipArchive::class)) {
    throw new Exception('zip_extension_missing', EQ_ERROR_INVALID_CONFIG);
}

$allowed_extensions = [];
foreach((array) $params['allowed_extensions'] as $allowed_extension) {
    if(!is_scalar($allowed_extension)) {
        continue;
    }

    $allowed_extension = strtolower(ltrim(trim((string) $allowed_extension), '.'));
    if($allowed_extension !== '' && $allowed_extension !== 'zip') {
        $allowed_extensions[$allowed_extension] = true;
    }
}

$max_entries = 100;
$max_uncompressed_size = (int) constant('UPLOAD_MAX_FILE_SIZE');

$tmp_file = null;
$zip = null;
$zip_opened = false;
$created_document_ids = [];

try {
    $tmp_file = tempnam(sys_get_temp_dir(), 'document_zip_');
    if($tmp_file === false) {
        throw new Exception('archive_temporary_file_failed', EQ_ERROR_UNKNOWN);
    }

    $written = file_put_contents($tmp_file, $document['data']);
    if($written === false || $written !== strlen((string) $document['data'])) {
        throw new Exception('archive_temporary_file_failed', EQ_ERROR_UNKNOWN);
    }

    $zip = new ZipArchive();
    $open_result = $zip->open($tmp_file, ZipArchive::CHECKCONS);
    if($open_result !== true) {
        trigger_error("APP::unable to open ZIP document {$document['id']}: error code {$open_result}", EQ_REPORT_WARNING);
        throw new Exception('invalid_zip_archive', EQ_ERROR_INVALID_PARAM);
    }
    $zip_opened = true;

    if($zip->numFiles > $max_entries) {
        throw new Exception('archive_too_many_entries', EQ_ERROR_INVALID_PARAM);
    }

    $entries = [];
    $skipped = [];
    $known_paths = [];
    $total_uncompressed_size = 0;

    for($index = 0; $index < $zip->numFiles; ++$index) {
        $stat = $zip->statIndex($index);
        if($stat === false) {
            throw new Exception('invalid_archive_entry', EQ_ERROR_INVALID_PARAM);
        }

        $source_path = str_replace('\\', '/', (string) $stat['name']);
        if($source_path === '' || substr($source_path, -1) === '/') {
            continue;
        }

        if(strpos($source_path, "\0") !== false
            || substr($source_path, 0, 1) === '/'
            || preg_match('/^[A-Za-z]:\//', $source_path)
            || in_array('..', explode('/', $source_path), true)) {
            throw new Exception('invalid_archive_entry_path', EQ_ERROR_INVALID_PARAM);
        }

        $name = basename($source_path);
        if(strpos($source_path, '__MACOSX/') === 0 || $name === '.DS_Store') {
            $skipped[] = [
                'source_path'    => $source_path,
                'reason'         => 'system_metadata'
            ];
            continue;
        }

        if(isset($known_paths[$source_path])) {
            throw new Exception('duplicate_archive_entry', EQ_ERROR_INVALID_PARAM);
        }
        $known_paths[$source_path] = true;

        $entry_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if($entry_extension === 'zip') {
            throw new Exception('nested_zip_archive_not_allowed', EQ_ERROR_INVALID_PARAM);
        }

        if(count($allowed_extensions) > 0 && !isset($allowed_extensions[$entry_extension])) {
            $skipped[] = [
                'source_path'    => $source_path,
                'reason'         => 'unsupported_extension'
            ];
            continue;
        }

        $entry_size = (int) $stat['size'];
        if($entry_size < 0 || $entry_size > $max_uncompressed_size) {
            throw new Exception('archive_entry_too_large', EQ_ERROR_INVALID_PARAM);
        }

        $total_uncompressed_size += $entry_size;
        if($total_uncompressed_size > $max_uncompressed_size) {
            throw new Exception('archive_uncompressed_size_exceeded', EQ_ERROR_INVALID_PARAM);
        }

        $entries[] = [
            'index'         => $index,
            'name'          => $name,
            'source_path'   => $source_path,
            'extension'     => $entry_extension,
            'size'          => $entry_size
        ];
    }

    foreach($entries as $sequence => $entry) {
        $data = $zip->getFromIndex($entry['index']);
        if($data === false) {
            throw new Exception('archive_entry_unreadable', EQ_ERROR_INVALID_PARAM);
        }

        $document_values = [
            'name'                  => $entry['name'],
            'data'                  => $data,
            'origin_document_id'    => $document['id'],
            'origin_relation_type'  => 'attachment',
            'source'                => 'processing'
        ];

        foreach(['organization_id', 'document_visibility', 'document_type_id', 'document_subtype_id'] as $field) {
            if(isset($document[$field])) {
                $document_values[$field] = $document[$field];
            }
        }

        $derived_document = Document::create($document_values)->first();
        if(!$derived_document) {
            throw new Exception('archive_document_creation_failed', EQ_ERROR_UNKNOWN);
        }

        $created_document_ids[] = $derived_document['id'];
        $entries[$sequence]['id'] = $derived_document['id'];
        unset($entries[$sequence]['index']);
        $entries[$sequence]['sequence'] = $sequence + 1;
    }

    $result = [
        'documents' => $entries,
        'skipped'   => $skipped
    ];
}
catch(Throwable $e) {
    if(count($created_document_ids) > 0) {
        try {
            Document::ids($created_document_ids)->delete(true);
        }
        catch(Throwable $cleanup_error) {
            trigger_error(
                'APP::unable to remove derived documents after failed ZIP extraction: ' . $cleanup_error->getMessage(),
                EQ_REPORT_ERROR
            );
        }
    }

    $safe_errors = [
        'archive_temporary_file_failed',
        'invalid_zip_archive',
        'archive_too_many_entries',
        'invalid_archive_entry',
        'invalid_archive_entry_path',
        'duplicate_archive_entry',
        'nested_zip_archive_not_allowed',
        'archive_entry_too_large',
        'archive_uncompressed_size_exceeded',
        'archive_entry_unreadable',
        'archive_document_creation_failed'
    ];

    if(in_array($e->getMessage(), $safe_errors, true)) {
        throw $e;
    }

    trigger_error('APP::unexpected ZIP extraction failure: ' . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('archive_extraction_failed', EQ_ERROR_UNKNOWN);
}
finally {
    if($zip_opened) {
        $zip->close();
    }
    if(is_string($tmp_file) && file_exists($tmp_file)) {
        unlink($tmp_file);
    }
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
