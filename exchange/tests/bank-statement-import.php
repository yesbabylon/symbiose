<?php

use document\document\Document;
use exchange\import\finance\BankStatementImport;
use finance\bank\BankAccount;
use finance\bank\BankStatement;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$headers = [
    'Account', 'Account holder', 'Bic', 'Statement number', 'Statement currency',
    'Opening balance date', 'Opening balance', 'Closing balance date', 'Closing balance',
    'Entry date', 'Value date', 'Transaction amount', 'Transaction currency',
    'Transaction type', 'Client reference', 'Structured Reference', 'Unstructured Reference',
    'Bank reference', 'Counterparty name', 'Counterparty account', 'Counterparty bank BIC',
    'Counterparty data', 'Transaction message', 'Sequence number'
];

$build_xlsx = static function(array $rows) use($headers): string {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([$headers], $rows), null, 'A1');
    $tmp = tempnam(sys_get_temp_dir(), 'bank_import_test_');
    if($tmp === false) {
        throw new RuntimeException('Unable to create a temporary XLSX file.');
    }
    try {
        (new Xlsx($spreadsheet))->save($tmp);
        $binary = file_get_contents($tmp);
        if($binary === false) {
            throw new RuntimeException('Unable to read the temporary XLSX file.');
        }
        return $binary;
    }
    finally {
        $spreadsheet->disconnectWorksheets();
        if(file_exists($tmp)) {
            unlink($tmp);
        }
    }
};

$arrange = static function(bool $balanced = true, bool $duplicate = false) use($build_xlsx): array {
    $iban = 'BE68539007547034';
    $bank_account = BankAccount::search(['bank_account_iban', '=', $iban])->first();
    $created_bank_account = false;
    if(!$bank_account) {
        $bank_account = BankAccount::create([
                'organization_id'   => 1,
                'bank_name'         => 'Bank import test',
                'bank_account_iban' => $iban,
                'bank_account_bic'  => 'KREDBEBB'
            ])
            ->first();
        $created_bank_account = true;
    }

    $number = 'TEST-'.bin2hex(random_bytes(5));
    $amount = $balanced ? 25.0 : 24.0;
    $row = [
        $iban, 'Test organization', 'KREDBEBB', $number, 'EUR',
        '2026-01-01', 100.0, '2026-01-31', 125.0,
        '2026-01-15', '2026-01-16', $amount, 'EUR',
        '01 Transfer', 'CLIENT-1', '+++123/4567/89012+++', '',
        'BANK-1', 'Counterparty', 'BE71096123456769', 'GKCCBEBB',
        '', '', 1
    ];

    $existing_statement_id = null;
    if($duplicate) {
        $existing = BankStatement::create([
                'bank_account_id'     => $bank_account['id'],
                'statement_number'    => $number,
                'statement_currency'  => 'EUR',
                'opening_date'        => strtotime('2026-01-01'),
                'date'                => strtotime('2026-01-31'),
                'old_balance'         => 100.0,
                'new_balance'         => 125.0,
                'bank_account_number' => $iban,
                'bank_account_bic'    => 'KREDBEBB'
            ])
            ->first();
        $existing_statement_id = $existing['id'];
    }

    return [
        'bank_account_id'         => $bank_account['id'],
        'created_bank_account'    => $created_bank_account,
        'existing_statement_id'   => $existing_statement_id,
        'statement_number'        => $number,
        'binary_base64'           => base64_encode($build_xlsx([$row])),
        'balanced'                => $balanced,
        'duplicate'               => $duplicate
    ];
};

$act = static function(array $fixture): array {
    $import = BankStatementImport::create([
            'state' => 'draft',
            'name'  => ($fixture['import_name'] ?? $fixture['statement_number']).'.xlsx',
            'data'  => base64_decode($fixture['binary_base64'], true)
        ])
        ->read(['id'])
        ->first(true);
    BankStatementImport::id($import['id'])->update(['state' => 'instance']);

    $import = BankStatementImport::id($import['id'])
        ->read(['id', 'status', 'logs', 'document_id'])
        ->first(true);
    $statement_numbers = $fixture['statement_numbers'] ?? [$fixture['statement_number']];
    $statements = BankStatement::search(['statement_number', 'in', $statement_numbers])
        ->read(['id', 'statement_number', 'is_balanced', 'statement_lines_ids', 'document_id' => ['id', 'name']]);
    $source_document_ids = [];
    foreach($statements as $statement) {
        if($statement['document_id']) {
            $source_document_ids[] = $statement['document_id']['id'];
        }
    }
    $documents = Document::ids(array_values(array_unique(array_merge(
            [$import['document_id']],
            $source_document_ids
        ))))
        ->read(['id', 'is_origin', 'origin_document_id', 'origin_relation_type']);
    $documents_by_id = [];
    foreach($documents as $document) {
        $documents_by_id[$document['id']] = $document;
    }

    unset($fixture['binary_base64']);

    return array_merge($fixture, [
        'import'       => $import,
        'statement_ids'=> $statements->ids(),
        'source_document_ids' => $source_document_ids,
        'statements'   => $statements->toArray(),
        'documents'    => $documents_by_id
    ]);
};

$rollback = static function($result): void {
    if(!is_array($result)) {
        return;
    }
    if(!empty($result['statement_ids'])) {
        BankStatement::ids($result['statement_ids'])->delete(true);
    }
    if(!empty($result['source_document_ids'])) {
        Document::ids($result['source_document_ids'])->delete(true);
    }
    if(!empty($result['import']['id'])) {
        BankStatementImport::id($result['import']['id'])->delete(true);
    }
    if(!empty($result['import']['document_id'])) {
        Document::id($result['import']['document_id'])->delete(true);
    }
    if(!empty($result['created_bank_account']) && !empty($result['bank_account_id'])) {
        BankAccount::id($result['bank_account_id'])->delete(true);
    }
    if(!empty($result['created_bank_account_ids'])) {
        BankAccount::ids($result['created_bank_account_ids'])->delete(true);
    }
};

$tests = [
    '0201' => [
        'description' => 'Import a balanced XLSX bank statement.',
        'arrange'     => static function() use($arrange) { return $arrange(true, false); },
        'act'         => $act,
        'assert'      => static function(array $result): bool {
            if($result['import']['status'] !== 'imported') {
                throw new RuntimeException('Expected an imported status: '.$result['import']['logs']);
            }
            if(count($result['statements']) !== 1 || !$result['statements'][0]['is_balanced']) {
                throw new RuntimeException('Expected one balanced bank statement.');
            }
            if(count($result['statements'][0]['statement_lines_ids']) !== 1) {
                throw new RuntimeException('Expected one bank statement line.');
            }
            if(!$result['statements'][0]['document_id']
                || $result['statements'][0]['document_id']['id'] === $result['import']['document_id']
                || substr($result['statements'][0]['document_id']['name'], -8) !== '(1).xlsx') {
                throw new RuntimeException('Expected a dedicated normalized XLSX source document.');
            }
            $origin_document = $result['documents'][$result['import']['document_id']] ?? null;
            $source_document = $result['documents'][$result['statements'][0]['document_id']['id']] ?? null;
            if(!$origin_document || !$origin_document['is_origin']) {
                throw new RuntimeException('Expected the uploaded document to be marked as origin.');
            }
            if(!$source_document
                || $source_document['is_origin']
                || $source_document['origin_document_id'] !== $origin_document['id']
                || $source_document['origin_relation_type'] !== 'segment') {
                throw new RuntimeException('Expected the normalized document to be a segment of the uploaded document.');
            }
            return true;
        },
        'rollback'    => $rollback
    ],
    '0202' => [
        'description' => 'Reject an unbalanced XLSX bank statement without partial creation.',
        'arrange'     => static function() use($arrange) { return $arrange(false, false); },
        'act'         => $act,
        'assert'      => static function(array $result): bool {
            if($result['import']['status'] !== 'failing') {
                throw new RuntimeException('Expected a failing status for an unbalanced statement.');
            }
            if(count($result['statements']) !== 0) {
                throw new RuntimeException('An unbalanced statement was partially imported.');
            }
            return true;
        },
        'rollback'    => $rollback
    ],
    '0203' => [
        'description' => 'Skip a bank statement that is already imported.',
        'arrange'     => static function() use($arrange) { return $arrange(true, true); },
        'act'         => $act,
        'assert'      => static function(array $result): bool {
            if($result['import']['status'] !== 'imported') {
                throw new RuntimeException('A duplicate should be skipped without failing the import.');
            }
            if(count($result['statements']) !== 1 || $result['statements'][0]['id'] !== $result['existing_statement_id']) {
                throw new RuntimeException('The duplicate import created an additional statement.');
            }
            if(strpos($result['import']['logs'], 'already imported') === false) {
                throw new RuntimeException('The duplicate warning is missing from import logs.');
            }
            return true;
        },
        'rollback'    => $rollback
    ],
    '0204' => [
        'description' => 'Split a multi-account XLSX into one source document per bank statement.',
        'arrange'     => static function() use($build_xlsx): array {
            $accounts = [
                ['iban' => 'BE68539007547034', 'bic' => 'KREDBEBB', 'opening' => 100.0, 'closing' => 125.0, 'amount' => 25.0],
                ['iban' => 'BE71096123456769', 'bic' => 'GKCCBEBB', 'opening' => 200.0, 'closing' => 190.0, 'amount' => -10.0]
            ];
            $created_bank_account_ids = [];
            $primary_account = BankAccount::search(['bank_account_iban', '=', $accounts[0]['iban']])
                ->read(['id', 'organization_id'])
                ->first(true);
            if(!$primary_account) {
                $primary_account = BankAccount::create([
                        'organization_id'   => 1,
                        'bank_name'         => 'Multi-account import test',
                        'bank_account_iban' => $accounts[0]['iban'],
                        'bank_account_bic'  => $accounts[0]['bic']
                    ])
                    ->read(['id', 'organization_id'])
                    ->first(true);
                $created_bank_account_ids[] = $primary_account['id'];
            }
            foreach($accounts as $account) {
                $bank_account = BankAccount::search(['bank_account_iban', '=', $account['iban']])->first();
                if(!$bank_account) {
                    $bank_account = BankAccount::create([
                            'organization_id'   => $primary_account['organization_id'],
                            'bank_name'         => 'Multi-account import test',
                            'bank_account_iban' => $account['iban'],
                            'bank_account_bic'  => $account['bic']
                        ])
                        ->first();
                    $created_bank_account_ids[] = $bank_account['id'];
                }
            }

            $suffix = bin2hex(random_bytes(5));
            $numbers = ['MULTI-A-'.$suffix, 'MULTI-B-'.$suffix];
            $rows = [];
            foreach($accounts as $index => $account) {
                $rows[] = [
                    $account['iban'], 'Test organization', $account['bic'], $numbers[$index], 'EUR',
                    '2026-03-01', $account['opening'], '2026-03-31', $account['closing'],
                    '2026-03-15', '2026-03-16', $account['amount'], 'EUR',
                    '01 Transfer', 'CLIENT-'.$index, '', 'Multi-account transaction',
                    'BANK-'.$index, 'Counterparty', 'BE68539007547034', 'KREDBEBB',
                    '', '', 1
                ];
            }

            return [
                'import_name'              => 'MULTI-'.$suffix,
                'statement_number'         => $numbers[0],
                'statement_numbers'        => $numbers,
                'binary_base64'            => base64_encode($build_xlsx($rows)),
                'created_bank_account_ids' => $created_bank_account_ids
            ];
        },
        'act'         => $act,
        'assert'      => static function(array $result): bool {
            if($result['import']['status'] !== 'imported' || count($result['statements']) !== 2) {
                throw new RuntimeException('Expected two imported bank statements.');
            }
            $document_ids = [];
            foreach($result['statements'] as $statement) {
                if(!$statement['document_id']) {
                    throw new RuntimeException('A bank statement has no source document.');
                }
                $document_ids[] = $statement['document_id']['id'];
            }
            if(count(array_unique($document_ids)) !== 2
                || in_array($result['import']['document_id'], $document_ids, true)) {
                throw new RuntimeException('Bank statements did not receive distinct normalized source documents.');
            }
            $origin_document = $result['documents'][$result['import']['document_id']] ?? null;
            if(!$origin_document || !$origin_document['is_origin']) {
                throw new RuntimeException('Expected the multi-account upload to be marked as origin.');
            }
            foreach($document_ids as $document_id) {
                $source_document = $result['documents'][$document_id] ?? null;
                if(!$source_document
                    || $source_document['is_origin']
                    || $source_document['origin_document_id'] !== $origin_document['id']
                    || $source_document['origin_relation_type'] !== 'segment') {
                    throw new RuntimeException('Expected every normalized document to be a segment of the upload.');
                }
            }
            return true;
        },
        'rollback'    => $rollback
    ]
];
