<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use Codelicious\Coda\Parser;
use equal\text\TextTransformer;
use exchange\import\finance\BankStatementImport;
use finance\bank\BankStatement;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse a CODA bank statement import document.',
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

$content = str_replace("\r\n", "\n", $import['document_id']['data']);
$lines = array_map(static function($line) {
    return TextTransformer::toAscii($line);
}, explode("\n", $content));

$map_family = [
    '01' => 'transfer', '02' => 'transfer', '03' => 'cheque', '04' => 'card',
    '05' => 'direct_debit', '07' => 'bill_of_exchange', '09' => 'cash',
    '11' => 'securities', '13' => 'loan', '30' => 'misc',
    '35' => 'account_settlement', '41' => 'transfer', '80' => 'bank_fee'
];
$map_operation = [
    '0101' => 'transfer_out', '0103' => 'transfer_out', '0150' => 'transfer_in',
    '0151' => 'transfer_in', '0152' => 'transfer_in', '0154' => 'transfer_rejected',
    '0201' => 'transfer_out', '0250' => 'transfer_in', '0301' => 'cheque_payment',
    '0338' => 'cheque_rejected', '0402' => 'card_payment', '0404' => 'card_withdrawal',
    '0450' => 'card_payment_received', '0501' => 'direct_debit_out',
    '0503' => 'direct_debit_rejected', '0505' => 'direct_debit_refund',
    '0550' => 'direct_debit_in', '0901' => 'cash_withdrawal', '0950' => 'cash_deposit',
    '1101' => 'securities_purchase', '1150' => 'securities_sale',
    '1301' => 'loan_repayment', '1350' => 'loan_disbursement',
    '3501' => 'account_settlement_out', '3550' => 'account_settlement_in'
];
$parse_account = static function($value): array {
    $value = strtoupper(preg_replace('/\s+/', '', trim((string) $value)));
    $suffix = null;
    if(preg_match('/^[A-Z]{2}/', $value) && strpos($value, '-') !== false) {
        [$value, $suffix] = explode('-', $value, 2);
        $suffix = trim($suffix) ?: null;
    }
    return [BankStatement::convertBbanToIban($value), $suffix];
};

try {
    $parsed_statements = (new Parser())->parse($lines);
}
catch(Throwable $e) {
    trigger_error('APP::unable to parse CODA: '.$e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('invalid_coda_document', EQ_ERROR_INVALID_PARAM);
}

$result = [];
foreach($parsed_statements as $statement) {
    $account = $statement->getAccount();
    [$account_iban, $account_suffix] = $parse_account($account->getNumber());
    $normalized = [
        'account_iban'       => $account_iban,
        'account_holder'     => $account->getName(),
        'bank_bic'           => $account->getBic(),
        'account_suffix'     => $account_suffix,
        'statement_number'   => (string) $statement->getSequenceNumber(),
        'statement_currency' => $account->getCurrencyCode() ?: 'EUR',
        'opening_date'       => date('Y-m-d', $statement->getDate()->getTimestamp()),
        'opening_balance'    => round((float) $statement->getInitialBalance(), 2),
        'closing_date'       => date('Y-m-d', $statement->getNewDate()->getTimestamp()),
        'closing_balance'    => round((float) $statement->getNewBalance(), 2),
        'transactions'       => []
    ];

    foreach($statement->getTransactions() as $transaction) {
        $code = $transaction->getTransactionCode();
        $family = str_pad((string) $code->getFamily(), 2, '0', STR_PAD_LEFT);
        $operation = str_pad((string) $code->getOperation(), 2, '0', STR_PAD_LEFT);
        $transaction_type = $map_operation[$family.$operation] ?? $map_family[$family] ?? 'transfer';
        $counterparty = $transaction->getAccount();
        [$counterparty_iban, $counterparty_suffix] = $parse_account($counterparty->getNumber());

        $normalized['transactions'][] = [
            'entry_date'             => date('Y-m-d', $transaction->getTransactionDate()->getTimestamp()),
            'value_date'             => date('Y-m-d', $transaction->getValutaDate()->getTimestamp()),
            'amount'                 => round((float) $transaction->getAmount(), 2),
            'currency'               => $normalized['statement_currency'],
            'transaction_type'       => $transaction_type,
            'sequence_number'        => (int) $transaction->getStatementSequence(),
            'mandate_id'             => null,
            'client_reference'       => $transaction->getClientReference(),
            'structured_reference'   => $transaction->getStructuredMessage(),
            'unstructured_reference' => preg_replace('/\s+/', ' ', trim((string) $transaction->getMessage())),
            'bank_reference'         => null,
            'counterparty_name'      => $counterparty->getName(),
            'counterparty_iban'      => $counterparty_iban ?: null,
            'counterparty_suffix'    => $counterparty_suffix,
            'counterparty_bic'       => $counterparty->getBic() ?: null,
            'counterparty_details'   => null,
            'transaction_message'    => null
        ];
    }

    $result[] = $normalized;
}

$context->httpResponse()->body($result)->send();
