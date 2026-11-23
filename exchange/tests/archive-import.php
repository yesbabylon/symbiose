<?php

use document\document\Document;
use document\document\DocumentType;
use exchange\import\document\DocumentImport;
use exchange\import\finance\BankStatementImport;
use exchange\import\identity\ContactImport;
use finance\bank\BankAccount;
use finance\bank\BankStatement;
use identity\Contact;
use identity\Identity;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$build_csv = static function(string $firstname, string $lastname, string $email): string {
    $stream = fopen('php://temp', 'w+');
    if($stream === false) {
        throw new RuntimeException('Unable to create a CSV memory stream.');
    }

    try {
        fputcsv($stream, ['firstname', 'lastname', 'email'], ',', '"', '');
        fputcsv($stream, [$firstname, $lastname, $email], ',', '"', '');
        rewind($stream);

        return stream_get_contents($stream);
    }
    finally {
        fclose($stream);
    }
};

$build_zip = static function(array $entries): string {
    $tmp_file = tempnam(sys_get_temp_dir(), 'archive_import_test_');
    if($tmp_file === false) {
        throw new RuntimeException('Unable to create a temporary ZIP file.');
    }

    $zip = new ZipArchive();
    try {
        if($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open the temporary ZIP file.');
        }

        foreach($entries as $path => $data) {
            if(!$zip->addFromString($path, $data)) {
                throw new RuntimeException('Unable to add a ZIP test entry.');
            }
        }

        $zip->close();

        $data = file_get_contents($tmp_file);
        if($data === false) {
            throw new RuntimeException('Unable to read the temporary ZIP file.');
        }

        return $data;
    }
    finally {
        if(file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }
};

$build_bank_xlsx = static function(string $statement_number): string {
    $headers = [
        'Account', 'Account holder', 'Bic', 'Statement number', 'Statement currency',
        'Opening balance date', 'Opening balance', 'Closing balance date', 'Closing balance',
        'Entry date', 'Value date', 'Transaction amount', 'Transaction currency',
        'Transaction type', 'Client reference', 'Structured Reference', 'Unstructured Reference',
        'Bank reference', 'Counterparty name', 'Counterparty account', 'Counterparty bank BIC',
        'Counterparty data', 'Transaction message', 'Sequence number'
    ];
    $row = [
        'BE68539007547034', 'Test organization', 'KREDBEBB', $statement_number, 'EUR',
        '2026-04-01', 100.0, '2026-04-30', 125.0,
        '2026-04-15', '2026-04-16', 25.0, 'EUR',
        '01 Transfer', 'CLIENT-1', '', 'Archive bank transaction',
        'BANK-1', 'Counterparty', 'BE71096123456769', 'GKCCBEBB',
        '', '', 1
    ];

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->fromArray([$headers, $row], null, 'A1');
    $tmp_file = tempnam(sys_get_temp_dir(), 'archive_bank_test_');
    if($tmp_file === false) {
        throw new RuntimeException('Unable to create a temporary XLSX file.');
    }

    try {
        (new Xlsx($spreadsheet))->save($tmp_file);
        $data = file_get_contents($tmp_file);
        if($data === false) {
            throw new RuntimeException('Unable to read the temporary XLSX file.');
        }

        return $data;
    }
    finally {
        $spreadsheet->disconnectWorksheets();
        if(file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }
};

$rollback = static function(array $result): void {
    $identity_ids = $result['identity_ids'] ?? [];
    if(count($identity_ids) > 0) {
        $contact_ids = Contact::search(['identity_id', 'in', $identity_ids])->ids();
        Identity::ids($identity_ids)->update(['contact_id' => null]);
        if(count($contact_ids) > 0) {
            Contact::ids($contact_ids)->delete(true);
        }
        Identity::ids($identity_ids)->delete(true);
    }

    $child_import_ids = $result['child_import_ids'] ?? [];
    if(count($child_import_ids) > 0) {
        ContactImport::ids($child_import_ids)->delete(true);
    }
    if(!empty($result['root_import_id'])) {
        ContactImport::id($result['root_import_id'])->delete(true);
    }

    $child_document_ids = $result['child_document_ids'] ?? [];
    if(count($child_document_ids) > 0) {
        Document::ids($child_document_ids)->delete(true);
    }
    if(!empty($result['root_document_id'])) {
        Document::id($result['root_document_id'])->delete(true);
    }
    if(!empty($result['created_document_type_id'])) {
        DocumentType::id($result['created_document_type_id'])->delete(true);
    }
};

$rollback_document_import = static function(array $result): void {
    $child_import_ids = $result['child_import_ids'] ?? [];
    if(count($child_import_ids) > 0) {
        DocumentImport::ids($child_import_ids)->delete(true);
    }
    if(!empty($result['root_import_id'])) {
        DocumentImport::id($result['root_import_id'])->delete(true);
    }

    $child_document_ids = $result['child_document_ids'] ?? [];
    if(count($child_document_ids) > 0) {
        Document::ids($child_document_ids)->delete(true);
    }
    if(!empty($result['root_document_id'])) {
        Document::id($result['root_document_id'])->delete(true);
    }
};

$rollback_bank_archive = static function(array $result): void {
    $statement_ids = $result['statement_ids'] ?? [];
    if(count($statement_ids) > 0) {
        BankStatement::ids($statement_ids)->delete(true);
    }

    $statement_document_ids = $result['statement_document_ids'] ?? [];
    if(count($statement_document_ids) > 0) {
        Document::ids($statement_document_ids)->delete(true);
    }

    $child_import_ids = $result['child_import_ids'] ?? [];
    if(count($child_import_ids) > 0) {
        BankStatementImport::ids($child_import_ids)->delete(true);
    }
    if(!empty($result['root_import_id'])) {
        BankStatementImport::id($result['root_import_id'])->delete(true);
    }

    $child_document_ids = $result['child_document_ids'] ?? [];
    if(count($child_document_ids) > 0) {
        Document::ids($child_document_ids)->delete(true);
    }
    if(!empty($result['root_document_id'])) {
        Document::id($result['root_document_id'])->delete(true);
    }

    if(!empty($result['created_bank_account_id'])) {
        BankAccount::id($result['created_bank_account_id'])->delete(true);
    }
};

$tests = [
    '0301' => [
        'description' => 'Import supported contact files from a ZIP archive.',
        'arrange'     => static function() use($build_csv, $build_zip): array {
            $suffix = bin2hex(random_bytes(6));
            $emails = [
                "archive-first-{$suffix}@example.com",
                "archive-second-{$suffix}@example.com"
            ];

            return [
                'name'        => "contacts-{$suffix}.zip",
                'data_base64' => base64_encode($build_zip([
                    'contacts/first.csv' => $build_csv('Archive', 'First', $emails[0]),
                    'second.csv'         => $build_csv('Archive', 'Second', $emails[1])
                ])),
                'emails'      => $emails
            ];
        },
        'act'         => static function(array $fixture): array {
            $root = ContactImport::create([
                    'state' => 'draft',
                    'name'  => $fixture['name'],
                    'data'  => base64_decode($fixture['data_base64'], true)
                ])
                ->read(['id'])
                ->first(true);

            ContactImport::id($root['id'])->update(['state' => 'instance']);

            $root = ContactImport::id($root['id'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'has_errors',
                    'document_id',
                    'child_imports_ids'
                ])
                ->first(true);

            $children = ContactImport::ids($root['child_imports_ids'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'parent_import_id',
                    'source_path',
                    'document_id' => ['id', 'origin_document_id', 'origin_relation_type']
                ])
                ->toArray();

            $identities = Identity::search(['email', 'in', $fixture['emails']])
                ->read(['id', 'email'])
                ->toArray();

            return [
                'root'               => $root,
                'children'           => $children,
                'emails'             => $fixture['emails'],
                'identity_ids'       => array_column($identities, 'id'),
                'root_import_id'     => $root['id'],
                'root_document_id'   => $root['document_id'],
                'child_import_ids'   => array_column($children, 'id'),
                'child_document_ids' => array_values(array_filter(array_map(
                    static fn(array $child): ?int => $child['document_id']['id'] ?? null,
                    $children
                )))
            ];
        },
        'assert'      => static function(array $result): bool {
            if($result['root']['status'] !== 'imported'
                || $result['root']['preparation_status'] !== 'prepared'
                || $result['root']['has_errors']) {
                throw new RuntimeException('The ZIP root import was not completed successfully.');
            }
            if(count($result['children']) !== 2 || count($result['identity_ids']) !== 2) {
                throw new RuntimeException('The ZIP archive did not produce two imported contacts.');
            }

            $source_paths = [];
            foreach($result['children'] as $child) {
                if($child['status'] !== 'imported'
                    || $child['preparation_status'] !== 'prepared'
                    || $child['parent_import_id'] !== $result['root']['id']) {
                    throw new RuntimeException('A ZIP child import has an invalid state.');
                }
                if($child['document_id']['origin_document_id'] !== $result['root']['document_id']
                    || $child['document_id']['origin_relation_type'] !== 'attachment') {
                    throw new RuntimeException('A ZIP child document is not attached to the archive document.');
                }
                $source_paths[] = $child['source_path'];
            }

            sort($source_paths);
            if($source_paths !== ['contacts/first.csv', 'second.csv']) {
                throw new RuntimeException('ZIP source paths were not preserved.');
            }

            return true;
        },
        'rollback'    => $rollback
    ],

    '0302' => [
        'description' => 'Reject a ZIP archive containing another ZIP archive.',
        'arrange'     => static function() use($build_csv, $build_zip): array {
            $suffix = bin2hex(random_bytes(6));
            $inner_zip = $build_zip([
                'contact.csv' => $build_csv('Nested', 'Archive', "nested-{$suffix}@example.com")
            ]);

            return [
                'name'        => "nested-{$suffix}.zip",
                'data_base64' => base64_encode($build_zip(['inner.zip' => $inner_zip]))
            ];
        },
        'act'         => static function(array $fixture): array {
            $root = ContactImport::create([
                    'state' => 'draft',
                    'name'  => $fixture['name'],
                    'data'  => base64_decode($fixture['data_base64'], true)
                ])
                ->read(['id'])
                ->first(true);

            ContactImport::id($root['id'])->update(['state' => 'instance']);

            $root = ContactImport::id($root['id'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'has_errors',
                    'document_id',
                    'child_imports_ids'
                ])
                ->first(true);

            $derived_document_ids = Document::search(['origin_document_id', '=', $root['document_id']])->ids();

            return [
                'root'               => $root,
                'root_import_id'     => $root['id'],
                'root_document_id'   => $root['document_id'],
                'child_import_ids'   => $root['child_imports_ids'],
                'child_document_ids' => $derived_document_ids,
                'identity_ids'       => []
            ];
        },
        'assert'      => static function(array $result): bool {
            if($result['root']['status'] !== 'failing'
                || $result['root']['preparation_status'] !== 'failing'
                || !$result['root']['has_errors']) {
                throw new RuntimeException('The nested ZIP archive was not rejected.');
            }
            if(count($result['root']['child_imports_ids']) > 0 || count($result['child_document_ids']) > 0) {
                throw new RuntimeException('Nested ZIP rejection left derived objects behind.');
            }

            return true;
        },
        'rollback'    => $rollback
    ],

    '0303' => [
        'description' => 'Create multiple target documents from a document ZIP import.',
        'arrange'     => static function() use($build_zip): array {
            $suffix = bin2hex(random_bytes(6));
            $document_type = DocumentType::create([
                    'name'        => 'Archive import test',
                    'code'        => 'archive_import_'.$suffix,
                    'folder_code' => 'archive_import_'.$suffix
                ])
                ->first();

            return [
                'name'             => 'documents-'.$suffix.'.zip',
                'data_base64'      => base64_encode($build_zip([
                    'reports/first.txt' => 'First document',
                    'second.txt'        => 'Second document'
                ])),
                'document_type_id' => $document_type['id']
            ];
        },
        'act'         => static function(array $fixture): array {
            $root = DocumentImport::create([
                    'state'                 => 'draft',
                    'name'                  => $fixture['name'],
                    'data'                  => base64_decode($fixture['data_base64'], true),
                    'document_type_id'      => $fixture['document_type_id'],
                    'document_visibility'   => 'external'
                ])
                ->read(['id'])
                ->first(true);

            DocumentImport::id($root['id'])->update(['state' => 'instance']);

            $root = DocumentImport::id($root['id'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'document_id',
                    'child_imports_ids'
                ])
                ->first(true);

            $children = DocumentImport::ids($root['child_imports_ids'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'document_id' => [
                        'id',
                        'origin_document_id',
                        'origin_relation_type',
                        'document_type_id',
                        'document_visibility'
                    ]
                ])
                ->toArray();

            return [
                'root'               => $root,
                'children'           => $children,
                'document_type_id'   => $fixture['document_type_id'],
                'created_document_type_id' => $fixture['document_type_id'],
                'root_import_id'     => $root['id'],
                'root_document_id'   => $root['document_id'],
                'child_import_ids'   => array_column($children, 'id'),
                'child_document_ids' => array_values(array_filter(array_map(
                    static fn(array $child): ?int => $child['document_id']['id'] ?? null,
                    $children
                )))
            ];
        },
        'assert'      => static function(array $result): bool {
            if($result['root']['status'] !== 'imported'
                || $result['root']['preparation_status'] !== 'prepared'
                || count($result['children']) !== 2) {
                throw new RuntimeException('The document ZIP import was not completed successfully.');
            }

            foreach($result['children'] as $child) {
                $document = $child['document_id'];
                if($child['status'] !== 'imported'
                    || $child['preparation_status'] !== 'prepared'
                    || $document['origin_document_id'] !== $result['root']['document_id']
                    || $document['origin_relation_type'] !== 'attachment'
                    || $document['document_type_id'] !== $result['document_type_id']
                    || $document['document_visibility'] !== 'external') {
                    throw new RuntimeException('A document ZIP child did not preserve its metadata.');
                }
            }

            return true;
        },
        'rollback'    => $rollback_document_import
    ],

    '0304' => [
        'description' => 'Import bank statement files from a ZIP archive.',
        'arrange'     => static function() use($build_bank_xlsx, $build_zip): array {
            $iban = 'BE68539007547034';
            $bank_account = BankAccount::search(['bank_account_iban', '=', $iban])->first();
            $created_bank_account_id = null;
            if(!$bank_account) {
                $bank_account = BankAccount::create([
                        'organization_id'   => 1,
                        'bank_name'         => 'Archive bank import test',
                        'bank_account_iban' => $iban,
                        'bank_account_bic'  => 'KREDBEBB'
                    ])
                    ->first();
                $created_bank_account_id = $bank_account['id'];
            }

            $suffix = bin2hex(random_bytes(6));
            $statement_numbers = [
                'ZIP-A-'.$suffix,
                'ZIP-B-'.$suffix
            ];

            return [
                'name'                    => 'bank-statements-'.$suffix.'.zip',
                'data_base64'             => base64_encode($build_zip([
                    'bank/first.xlsx' => $build_bank_xlsx($statement_numbers[0]),
                    'second.xlsx'     => $build_bank_xlsx($statement_numbers[1])
                ])),
                'statement_numbers'       => $statement_numbers,
                'created_bank_account_id' => $created_bank_account_id
            ];
        },
        'act'         => static function(array $fixture): array {
            $root = BankStatementImport::create([
                    'state' => 'draft',
                    'name'  => $fixture['name'],
                    'data'  => base64_decode($fixture['data_base64'], true)
                ])
                ->read(['id'])
                ->first(true);

            BankStatementImport::id($root['id'])->update(['state' => 'instance']);

            $root = BankStatementImport::id($root['id'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'has_errors',
                    'document_id',
                    'child_imports_ids'
                ])
                ->first(true);

            $children = BankStatementImport::ids($root['child_imports_ids'])
                ->read([
                    'id',
                    'status',
                    'preparation_status',
                    'document_id' => ['id', 'origin_document_id', 'origin_relation_type']
                ])
                ->toArray();

            $statements = BankStatement::search(['statement_number', 'in', $fixture['statement_numbers']])
                ->read(['id', 'statement_number', 'document_id' => ['id', 'origin_document_id', 'origin_relation_type']])
                ->toArray();

            $statement_document_ids = array_values(array_filter(array_map(
                static fn(array $statement): ?int => $statement['document_id']['id'] ?? null,
                $statements
            )));

            return [
                'root'                    => $root,
                'children'                => $children,
                'statements'              => $statements,
                'statement_ids'           => array_column($statements, 'id'),
                'statement_document_ids'  => $statement_document_ids,
                'root_import_id'          => $root['id'],
                'root_document_id'        => $root['document_id'],
                'child_import_ids'        => array_column($children, 'id'),
                'child_document_ids'      => array_values(array_filter(array_map(
                    static fn(array $child): ?int => $child['document_id']['id'] ?? null,
                    $children
                ))),
                'created_bank_account_id' => $fixture['created_bank_account_id']
            ];
        },
        'assert'      => static function(array $result): bool {
            if($result['root']['status'] !== 'imported'
                || $result['root']['preparation_status'] !== 'prepared'
                || $result['root']['has_errors']
                || count($result['children']) !== 2
                || count($result['statements']) !== 2) {
                throw new RuntimeException('The bank statement ZIP import was not completed successfully.');
            }

            $child_document_ids = [];
            foreach($result['children'] as $child) {
                if($child['status'] !== 'imported'
                    || $child['preparation_status'] !== 'prepared'
                    || $child['document_id']['origin_document_id'] !== $result['root']['document_id']
                    || $child['document_id']['origin_relation_type'] !== 'attachment') {
                    throw new RuntimeException('A bank statement ZIP child has an invalid state.');
                }
                $child_document_ids[] = $child['document_id']['id'];
            }

            foreach($result['statements'] as $statement) {
                if(!in_array($statement['document_id']['origin_document_id'], $child_document_ids, true)
                    || $statement['document_id']['origin_relation_type'] !== 'segment') {
                    throw new RuntimeException('A bank statement document is not a segment of an archive entry.');
                }
            }

            return true;
        },
        'rollback'    => $rollback_bank_archive
    ]
];
