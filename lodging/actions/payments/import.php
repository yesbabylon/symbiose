<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\pay\BankStatement;
use sale\pay\BankStatementLine;

list($params, $providers) = announce([
    'description'   => "Imports the composition (hosts listing) for a given booking. If a composition already exists, it is reset.",
    'params'        => [
        'data' =>  [
            'description'   => 'TXT file holding the data to import as statements.',
            'type'          => 'file',
            'required'      => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();

if($user_id <= 0) {
    // restricted to identified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$content = $params['data'];

// get classes listing
$json = run('get', 'lodging_payments_coda-parse', ['data' => $content]);
$data = json_decode($json, true);

// relay error if any
if(isset($data['errors'])) {
    foreach($data['errors'] as $name => $message) throw new Exception($message, qn_error_code($name));
}

$result = [];

$statements = $data;

foreach($statements as $statement) {
    $fields = [
        'date'                  => $statement['date'],
        'old_balance'           => $statement['old_balance'],
        'new_balance'           => $statement['new_balance'],
        'bank_account_number'   => $statement['account']['number'],
        'bank_account_bic'      => $statement['account']['bic']
    ];

    $bank_statement = BankStatement::create($fields)->first();

    $result[] = $bank_statement;

    foreach($statement['transactions'] as $transaction) {
        $fields = [
            'bank_statement_id'     => $bank_statement['id'],
            'date'                  => $statement['date'],
            'amount'                => $transaction['amount'],
            'account_holder'        => $transaction['account']['name'],
            'account_iban'          => $transaction['account']['number'],
            'message'               => $transaction['message'],
            'structured_message'    => $transaction['structured_message']
        ];
        BankStatementLine::create($fields);
    }
}

$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();