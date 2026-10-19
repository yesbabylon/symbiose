<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\finance\BankStatementImport;
use document\document\Document;
use document\document\DocumentType;
use finance\bank\BankStatement;
use finance\bank\BankStatementLine;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

[$params, $providers] = eQual::announce([
    'type'          => 'do',
    'name'          => 'import',
    'package_name'  => 'exchange',
    'description'   => 'Create bank statements and lines from a checked import.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'exchange\import\finance\BankStatementImport',
            'required'          => true
        ]
    ],
    'access'        => ['visibility' => 'protected'],
    'response'      => ['accept-origin' => '*', 'content-type' => 'application/json'],
    'providers'     => ['context']
]);

['context' => $context] = $providers;

$import = BankStatementImport::id($params['id'])
    ->read(['status', 'document_id' => ['id', 'name']])
    ->first();
if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}
if($import['status'] !== 'ready') {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}

$validation = eQual::run('get', 'exchange_import_finance_BankStatementImport_validate', ['id' => $params['id']]);
$result = [
    'created'   => 0,
    'skipped'   => 0,
    'errors'    => $validation['errors'],
    'warnings'  => $validation['warnings'],
    'processed' => count($validation['statements']),
    'logs'      => $validation['logs']
];

if($result['errors'] > 0) {
    $context->httpResponse()->body($result)->send();
    return;
}

$resolveCommunication = static function(array $transaction): array {
    $structured = trim((string) ($transaction['structured_reference'] ?? ''));
    if($structured !== '') {
        $normalized = str_replace(' ', '', $structured);
        if(preg_match('/^RF\d{2}[A-Z0-9]{1,21}$/', $normalized)) {
            return [substr($structured, 0, 255), 'RF'];
        }
        if(preg_match('/^\+{3}\d{3}\/\d{4}\/\d{5}\+{3}$/', $structured)) {
            return [substr($structured, 0, 255), 'VCS'];
        }
        return [substr($structured, 0, 255), 'SCOR'];
    }

    $free = trim((string) ($transaction['unstructured_reference'] ?? ''));
    if($free === '') {
        $free = trim((string) ($transaction['transaction_message'] ?? ''));
    }
    return [substr($free, 0, 255), 'free'];
};

$buildStatementXlsx = static function(array $statement): string {
    $headers = [
        'Account', 'Account holder', 'Bank', 'Account type', 'Bic',
        'Statement number', 'Statement currency', 'Opening balance date', 'Opening balance',
        'Closing balance date', 'Closing balance', 'Closing available balance', 'Entry date',
        'Value date', 'Transaction amount', 'Transaction currency', 'Transaction type',
        'Client reference', 'Structured Reference', 'Unstructured Reference', 'Bank reference',
        'Counterparty name', 'Counterparty account', 'Counterparty bank BIC', 'Counterparty data',
        'Transaction message', 'Sequence number', 'Reception Date/Time', 'stFreeMessage'
    ];
    $to_excel_date = static function($value) {
        if(!$value || ($timestamp = strtotime((string) $value)) === false) {
            return null;
        }
        return XlsDate::PHPToExcel((new DateTime())->setTimestamp($timestamp));
    };
    $account_identifier = static function($iban, $suffix): string {
        $identifier = (string) $iban;
        return $suffix ? $identifier.'-'.$suffix : $identifier;
    };

    $spreadsheet = new Spreadsheet();
    try {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $row_number = 2;
        foreach($statement['transactions'] as $transaction) {
            $row = [
                $account_identifier($statement['account_iban'] ?? '', $statement['account_suffix'] ?? null),
                $statement['account_holder'] ?? '',
                '',
                $statement['account_type'] ?? '',
                $statement['bank_bic'] ?? '',
                $statement['statement_number'] ?? '',
                $statement['statement_currency'] ?? '',
                $to_excel_date($statement['opening_date'] ?? null),
                $statement['opening_balance'] ?? null,
                $to_excel_date($statement['closing_date'] ?? null),
                $statement['closing_balance'] ?? null,
                null,
                $to_excel_date($transaction['entry_date'] ?? null),
                $to_excel_date($transaction['value_date'] ?? null),
                $transaction['amount'] ?? null,
                $transaction['currency'] ?? '',
                $transaction['transaction_type'] ?? '',
                $transaction['client_reference'] ?? '',
                $transaction['structured_reference'] ?? '',
                $transaction['unstructured_reference'] ?? '',
                $transaction['bank_reference'] ?? '',
                $transaction['counterparty_name'] ?? '',
                $account_identifier($transaction['counterparty_iban'] ?? '', $transaction['counterparty_suffix'] ?? null),
                $transaction['counterparty_bic'] ?? '',
                $transaction['counterparty_details'] ?? '',
                $transaction['transaction_message'] ?? '',
                $transaction['sequence_number'] ?? '',
                null,
                ''
            ];
            $sheet->fromArray($row, null, 'A'.$row_number);
            foreach(['H', 'J', 'M', 'N', 'AB'] as $column) {
                $sheet->getStyle($column.$row_number)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }
            $row_number++;
        }

        $stream = fopen('php://memory', 'w+b');
        if($stream === false) {
            throw new Exception('unable_to_open_xlsx_stream', EQ_ERROR_INVALID_CONFIG);
        }
        try {
            (new Xlsx($spreadsheet))->save($stream);
            rewind($stream);
            $binary = stream_get_contents($stream);
            if($binary === false) {
                throw new Exception('unable_to_read_xlsx_stream', EQ_ERROR_INVALID_PARAM);
            }
            return $binary;
        }
        finally {
            fclose($stream);
        }
    }
    finally {
        $spreadsheet->disconnectWorksheets();
    }
};

$created_statement_ids = [];
$created_document_ids = [];
try {
    $document_type = DocumentType::search(['code', '=', 'bank_statement'])->first();
    $source_basename = pathinfo($import['document_id']['name'], PATHINFO_FILENAME);

    foreach($validation['statements'] as $statement_index => $statement) {
        if($statement['_duplicate_id'] !== null) {
            $result['skipped']++;
            continue;
        }

        $document_values = [
            'name'                  => sprintf('%s(%d).xlsx', $source_basename, $statement_index + 1),
            'data'                  => $buildStatementXlsx($statement),
            'origin_document_id'    => $import['document_id']['id'],
            'origin_relation_type'  => 'segment'
        ];
        if($document_type) {
            $document_values['document_type_id'] = $document_type['id'];
        }
        $source_document = Document::create($document_values)
            ->first();
        if(!$source_document) {
            throw new Exception('unable_to_create_bank_statement_document', EQ_ERROR_UNKNOWN);
        }
        $created_document_ids[] = $source_document['id'];

        $opening_date = strtotime($statement['opening_date']);
        $closing_date = strtotime($statement['closing_date']);
        $bank_statement = BankStatement::create([
                'document_id'         => $source_document['id'],
                'bank_account_id'     => $statement['_bank_account_id'],
                'statement_number'    => $statement['statement_number'],
                'statement_currency'  => $statement['statement_currency'],
                'opening_date'        => $opening_date,
                'date'                => $closing_date,
                'old_balance'         => round((float) $statement['opening_balance'], 2),
                'new_balance'         => round((float) $statement['closing_balance'], 2),
                'bank_account_number' => $statement['account_iban'],
                'bank_account_bic'    => $statement['bank_bic'] ?? null,
                'bank_account_suffix' => $statement['account_suffix'] ?? null
            ])
            ->first();

        if(!$bank_statement) {
            throw new Exception('unable_to_create_bank_statement', EQ_ERROR_UNKNOWN);
        }
        $created_statement_ids[] = $bank_statement['id'];

        foreach($statement['transactions'] as $index => $transaction) {
            [$communication, $communication_type] = $resolveCommunication($transaction);
            $line = BankStatementLine::create([
                    'bank_statement_id'       => $bank_statement['id'],
                    'sequence_number'         => $transaction['sequence_number'] ?? ($index + 1),
                    'date'                    => strtotime($transaction['value_date']),
                    'communication'           => $communication,
                    'communication_type'      => $communication_type,
                    'message'                 => $transaction['unstructured_reference'] ?: ($transaction['transaction_message'] ?? null),
                    'structured_message'      => $transaction['structured_reference'] ?: null,
                    'mandate_identifier'      => $transaction['mandate_id'] ?? null,
                    'amount'                  => round((float) $transaction['amount'], 2),
                    'amount_currency'         => $transaction['currency'],
                    'transaction_type'        => $transaction['transaction_type'] ?: 'transfer',
                    'account_iban'            => $transaction['counterparty_iban'] ?: null,
                    'account_suffix'          => $transaction['counterparty_suffix'] ?? null,
                    'account_bic'             => $transaction['counterparty_bic'] ?: null,
                    'account_holder'          => $transaction['counterparty_name'] ?: null,
                    'status'                  => 'pending'
                ])
                ->first();
            if(!$line) {
                throw new Exception('unable_to_create_bank_statement_line', EQ_ERROR_UNKNOWN);
            }
        }

        BankStatement::id($bank_statement['id'])->do('refresh_status');
        $result['created']++;
        $result['logs'][] = "INFO - created bank statement {$bank_statement['id']} ({$statement['statement_number']})";
    }
}
catch(Throwable $e) {
    if(count($created_statement_ids)) {
        try {
            BankStatement::ids($created_statement_ids)->delete(true);
        }
        catch(Throwable $rollback_error) {
            trigger_error('APP::bank statement import rollback failed: '.$rollback_error->getMessage(), EQ_REPORT_ERROR);
        }
    }
    if(count($created_document_ids)) {
        try {
            Document::ids($created_document_ids)->delete(true);
        }
        catch(Throwable $rollback_error) {
            trigger_error('APP::bank statement document rollback failed: '.$rollback_error->getMessage(), EQ_REPORT_ERROR);
        }
    }
    $result['created'] = 0;
    $result['errors']++;
    $result['logs'][] = 'ERR - import rolled back: '.$e->getMessage();
    trigger_error('APP::unable to import bank statements: '.$e->getMessage(), EQ_REPORT_ERROR);
}

$context->httpResponse()->body($result)->send();
