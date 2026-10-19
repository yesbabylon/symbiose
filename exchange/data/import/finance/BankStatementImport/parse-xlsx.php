<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\finance\BankStatementImport;
use finance\bank\BankStatement;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse an Isabel XLSX bank statement import document.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'exchange\import\finance\BankStatementImport',
            'required'          => true
        ]
    ],
    'access'        => ['visibility' => 'protected'],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json',
        'charset'       => 'utf-8'
    ],
    'providers'     => ['context']
]);

['context' => $context] = $providers;

$import = BankStatementImport::id($params['id'])
    ->read(['document_id' => ['data']])
    ->first();
if(!$import || !isset($import['document_id']['data']) || !is_string($import['document_id']['data'])) {
    throw new Exception('missing_import_document', EQ_ERROR_INVALID_PARAM);
}

$tmp = null;
try {
    $tmp = tempnam(sys_get_temp_dir(), 'bank_xlsx_');
    if($tmp === false || file_put_contents($tmp, $import['document_id']['data']) === false) {
        throw new Exception('unable_to_write_temporary_xlsx');
    }
    $spreadsheet = IOFactory::createReader('Xlsx')->load($tmp);
}
catch(Throwable $e) {
    trigger_error('APP::unable to load bank XLSX: '.$e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('failed_loading_xlsx', EQ_ERROR_INVALID_PARAM);
}
finally {
    if(is_string($tmp) && file_exists($tmp)) {
        unlink($tmp);
    }
}

$rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
$header_row = array_shift($rows);
if(!is_array($header_row)) {
    throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
}

$known_headers = [
    'account', 'account holder', 'bic', 'statement number', 'statement currency',
    'opening balance date', 'opening balance', 'closing balance date', 'closing balance',
    'entry date', 'value date', 'transaction amount', 'transaction currency',
    'transaction type', 'client reference', 'structured reference', 'unstructured reference',
    'bank reference', 'counterparty name', 'counterparty account', 'counterparty bank bic',
    'counterparty data', 'transaction message', 'sequence number'
];
$required_headers = [
    'account', 'statement number', 'statement currency', 'opening balance date',
    'opening balance', 'closing balance date', 'closing balance', 'value date',
    'transaction amount', 'transaction currency'
];

$columns = [];
foreach($header_row as $column => $header) {
    $header = strtolower(trim((string) $header));
    if(!in_array($header, $known_headers, true)) {
        continue;
    }
    if(isset($columns[$header])) {
        throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
    }
    $columns[$header] = $column;
}
foreach($required_headers as $header) {
    if(!isset($columns[$header])) {
        throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
    }
}

$cell = static function(array $row, string $header) use($columns) {
    return isset($columns[$header]) ? ($row[$columns[$header]] ?? null) : null;
};
$parse_date = static function($value): ?string {
    if($value === null || $value === '') {
        return null;
    }
    if(is_numeric($value)) {
        return XlsDate::excelToDateTimeObject($value)->format('Y-m-d');
    }
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
};
$parse_amount = static function($value): ?float {
    if(is_float($value) || is_int($value)) {
        return round((float) $value, 2);
    }
    $normalized = str_replace([' ', "\xc2\xa0"], '', trim((string) $value));
    if(substr_count($normalized, ',') > 0 && substr_count($normalized, '.') > 0) {
        if(strrpos($normalized, ',') > strrpos($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }
        else {
            $normalized = str_replace(',', '', $normalized);
        }
    }
    elseif(substr_count($normalized, ',') === 1) {
        $normalized = str_replace(',', '.', $normalized);
    }
    return is_numeric($normalized) ? round((float) $normalized, 2) : null;
};
$parse_account = static function($value): array {
    $value = strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
    $suffix = null;
    if(preg_match('/^[A-Z]{2}/', $value) && strpos($value, '-') !== false) {
        [$value, $suffix] = explode('-', $value, 2);
        $suffix = trim($suffix) ?: null;
    }
    return [BankStatement::convertBbanToIban($value), $suffix];
};
$transaction_type = static function($value): string {
    $value = trim((string) $value);
    $families = [
        '01' => 'transfer', '02' => 'transfer', '03' => 'cheque', '04' => 'card',
        '05' => 'direct_debit', '07' => 'bill_of_exchange', '09' => 'cash',
        '11' => 'securities', '13' => 'loan', '30' => 'misc',
        '35' => 'account_settlement', '41' => 'transfer', '80' => 'bank_fee'
    ];
    if(preg_match('/^\s*(\d{2})/', $value, $matches)) {
        return $families[$matches[1]] ?? 'transfer';
    }
    $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $value));
    $allowed = ['transfer', 'cheque', 'card', 'direct_debit', 'bill_of_exchange', 'cash', 'securities', 'loan', 'misc', 'account_settlement', 'bank_fee'];
    return in_array($normalized, $allowed, true) ? $normalized : 'transfer';
};

$statements = [];
foreach($rows as $row) {
    if(!array_filter($row, static function($value) { return $value !== null && $value !== ''; })) {
        continue;
    }

    [$iban, $account_suffix] = $parse_account($cell($row, 'account'));
    $statement_number = trim((string) $cell($row, 'statement number'));
    $opening_date = $parse_date($cell($row, 'opening balance date'));
    $opening_balance = $parse_amount($cell($row, 'opening balance'));
    $closing_date = $parse_date($cell($row, 'closing balance date'));
    $closing_balance = $parse_amount($cell($row, 'closing balance'));
    $key = json_encode([$iban, $statement_number, $opening_date, $opening_balance, $closing_date, $closing_balance]);

    if(!isset($statements[$key])) {
        $statements[$key] = [
            'account_iban'       => $iban,
            'account_holder'     => trim((string) $cell($row, 'account holder')),
            'bank_bic'           => strtoupper(preg_replace('/\s+/', '', (string) $cell($row, 'bic'))),
            'account_suffix'     => $account_suffix,
            'statement_number'   => $statement_number,
            'statement_currency' => strtoupper(trim((string) $cell($row, 'statement currency'))),
            'opening_date'       => $opening_date,
            'opening_balance'    => $opening_balance,
            'closing_date'       => $closing_date,
            'closing_balance'    => $closing_balance,
            'transactions'       => []
        ];
    }

    $sequence = $cell($row, 'sequence number');
    [$counterparty_iban, $counterparty_suffix] = $parse_account($cell($row, 'counterparty account'));
    $statements[$key]['transactions'][] = [
        'entry_date'             => $parse_date($cell($row, 'entry date')),
        'value_date'             => $parse_date($cell($row, 'value date')),
        'amount'                 => $parse_amount($cell($row, 'transaction amount')),
        'currency'               => strtoupper(trim((string) $cell($row, 'transaction currency'))),
        'transaction_type'       => $transaction_type($cell($row, 'transaction type')),
        'sequence_number'        => is_numeric($sequence) ? (int) $sequence : count($statements[$key]['transactions']) + 1,
        'mandate_id'             => null,
        'client_reference'       => trim((string) $cell($row, 'client reference')),
        'structured_reference'   => trim((string) $cell($row, 'structured reference')),
        'unstructured_reference' => trim((string) $cell($row, 'unstructured reference')),
        'bank_reference'         => trim((string) $cell($row, 'bank reference')),
        'counterparty_name'      => trim((string) $cell($row, 'counterparty name')),
        'counterparty_iban'      => $counterparty_iban,
        'counterparty_suffix'    => $counterparty_suffix,
        'counterparty_bic'       => strtoupper(preg_replace('/\s+/', '', (string) $cell($row, 'counterparty bank bic'))),
        'counterparty_details'   => trim((string) $cell($row, 'counterparty data')),
        'transaction_message'    => trim((string) $cell($row, 'transaction message'))
    ];
}

$context->httpResponse()
    ->body(array_values($statements))
    ->send();
