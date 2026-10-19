<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\finance\BankStatementImport;
use finance\bank\BankAccount;
use finance\bank\BankStatement;

[$params, $providers] = eQual::announce([
    'description'   => 'Validate normalized bank statements before import.',
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

if(!BankStatementImport::id($params['id'])->read(['id'])->first()) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

$statements = eQual::run('get', 'exchange_import_finance_BankStatementImport_parse', ['id' => $params['id']]);
$result = [
    'errors'        => 0,
    'warnings'      => 0,
    'logs'          => [],
    'statements'    => []
];

if(!is_array($statements) || count($statements) === 0) {
    $result['errors']++;
    $result['logs'][] = 'ERR - empty import';
}

$batch_keys = [];
$is_valid_iban = static function(string $iban): bool {
    if(!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)) {
        return false;
    }
    $rearranged = substr($iban, 4).substr($iban, 0, 4);
    $remainder = 0;
    foreach(str_split($rearranged) as $character) {
        $digits = ctype_alpha($character) ? (string) (ord($character) - ord('A') + 10) : $character;
        foreach(str_split($digits) as $digit) {
            $remainder = (($remainder * 10) + (int) $digit) % 97;
        }
    }
    return $remainder === 1;
};
foreach((array) $statements as $index => $statement) {
    $number = trim((string) ($statement['statement_number'] ?? ''));
    $label = $number !== '' ? $number : '#'.($index + 1);
    $errors_before = $result['errors'];

    $iban = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($statement['account_iban'] ?? '')));
    if(!$is_valid_iban($iban)) {
        $result['errors']++;
        $result['logs'][] = "ERR - invalid account IBAN for statement {$label}";
    }
    if($number === '') {
        $result['errors']++;
        $result['logs'][] = "ERR - missing statement number for statement {$label}";
    }

    $currency = strtoupper((string) ($statement['statement_currency'] ?? ''));
    if(!preg_match('/^[A-Z]{3}$/', $currency)) {
        $result['errors']++;
        $result['logs'][] = "ERR - invalid currency for statement {$label}";
    }

    foreach(['opening_date', 'closing_date'] as $date_field) {
        $date = $statement[$date_field] ?? null;
        if(!$date || strtotime($date) === false) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid {$date_field} for statement {$label}";
        }
    }
    if(!empty($statement['opening_date']) && !empty($statement['closing_date'])
        && strtotime($statement['closing_date']) < strtotime($statement['opening_date'])) {
        $result['errors']++;
        $result['logs'][] = "ERR - closing date precedes opening date for statement {$label}";
    }
    foreach(['opening_balance', 'closing_balance'] as $amount_field) {
        if(!isset($statement[$amount_field]) || !is_numeric($statement[$amount_field])) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid {$amount_field} for statement {$label}";
        }
    }

    $transactions = $statement['transactions'] ?? [];
    if(!is_array($transactions) || count($transactions) === 0) {
        $result['errors']++;
        $result['logs'][] = "ERR - statement {$label} has no transactions";
    }

    $transactions_sum = 0.0;
    foreach((array) $transactions as $transaction_index => $transaction) {
        $row = $transaction_index + 1;
        if(!isset($transaction['amount']) || !is_numeric($transaction['amount'])) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid transaction amount at line {$row} of statement {$label}";
            continue;
        }
        $transactions_sum += round((float) $transaction['amount'], 2);
        $value_date = $transaction['value_date'] ?? null;
        if(!$value_date || strtotime($value_date) === false) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid value date at line {$row} of statement {$label}";
        }
        $transaction_currency = strtoupper((string) ($transaction['currency'] ?? ''));
        if(!preg_match('/^[A-Z]{3}$/', $transaction_currency)) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid transaction currency at line {$row} of statement {$label}";
        }
        $counterparty_iban = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($transaction['counterparty_iban'] ?? '')));
        if($counterparty_iban !== '' && !$is_valid_iban($counterparty_iban)) {
            $result['errors']++;
            $result['logs'][] = "ERR - invalid counterparty IBAN at line {$row} of statement {$label}";
        }
    }

    if(isset($statement['opening_balance'], $statement['closing_balance'])) {
        $delta = round((float) $statement['closing_balance'], 2) - round((float) $statement['opening_balance'], 2);
        if(abs($transactions_sum - $delta) >= 0.01) {
            $result['errors']++;
            $result['logs'][] = "ERR - statement {$label} is not balanced";
        }
    }

    $bank_account = null;
    if($iban !== '') {
        $bank_account = BankAccount::search(['bank_account_iban', '=', $iban])->first();
        if(!$bank_account) {
            $result['errors']++;
            $result['logs'][] = "ERR - no bank account found for IBAN {$iban}";
        }
    }

    $opening_date = isset($statement['opening_date']) ? strtotime($statement['opening_date']) : false;
    $closing_date = isset($statement['closing_date']) ? strtotime($statement['closing_date']) : false;
    $key = json_encode([
        $iban,
        $number,
        $opening_date,
        round((float) ($statement['opening_balance'] ?? 0), 2),
        $closing_date,
        round((float) ($statement['closing_balance'] ?? 0), 2)
    ]);

    $duplicate_id = null;
    if(isset($batch_keys[$key])) {
        $duplicate_id = 'batch';
    }
    elseif($bank_account && $opening_date !== false && $closing_date !== false) {
        $duplicate = BankStatement::search([
                ['bank_account_id', '=', $bank_account['id']],
                ['statement_number', '=', $number],
                ['opening_date', '=', $opening_date],
                ['old_balance', '=', round((float) $statement['opening_balance'], 2)],
                ['date', '=', $closing_date],
                ['new_balance', '=', round((float) $statement['closing_balance'], 2)]
            ])
            ->first();
        if($duplicate) {
            $duplicate_id = $duplicate['id'];
        }
    }

    if($duplicate_id !== null) {
        $result['warnings']++;
        $result['logs'][] = "WARN - statement {$label} is already imported and will be skipped";
    }
    else {
        $batch_keys[$key] = true;
    }

    $statement['account_iban'] = $iban;
    $statement['statement_currency'] = $currency;
    $statement['_bank_account_id'] = $bank_account['id'] ?? null;
    $statement['_duplicate_id'] = $duplicate_id;
    $result['statements'][] = $statement;

    if($result['errors'] === $errors_before && $duplicate_id === null) {
        $result['logs'][] = "INFO - statement {$label} is valid";
    }
}

$context->httpResponse()->body($result)->send();
