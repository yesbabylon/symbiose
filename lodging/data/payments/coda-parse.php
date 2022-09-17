<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use Codelicious\Coda\Parser;
use lodging\sale\booking\BankStatement;

list($params, $providers) = announce([
    'description'   => "Imports the composition (hosts listing) for a given booking. If a composition already exists, it is reset.",
    'params'        => [
        'data' =>  [
            'description'   => 'Raw CODA data to parse as statements.',
            'type'          => 'string',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['sale.default.user'],
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
    // retricted to identified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}


$content = $params['data'];
$size = strlen($content);

$content = str_replace("\r\n", "\n", $content);
$lines = explode("\n", $content);

$parser = new Parser();
$statements = $parser->parse($lines);


$result = [];

foreach ($statements as $statement) {
    $line = [
        'date'          => $statement->getDate()->getTimestamp(),
        'old_balance'   => $statement->getInitialBalance(),
        'new_balance'   => $statement->getNewBalance(),
    ];

    $account = $statement->getAccount();

    $line['account']  = [
        "name"      => $account->getName(),
        "number"    => $account->getNumber(),
        "iban"      => BankStatement::convertBbanToIban($account->getNumber()),
        "bic"       => $account->getBic(),
        "country"   => $account->getCountryCode()
    ];

    $line['transactions'] = [];

    foreach ($statement->getTransactions() as $transaction) {

        $account = $transaction->getAccount();

        $line['transactions'][] = [
            'account'   => [
                "name"      => $account->getName(),
                "bic"       => $account->getBic(),
                "number"    => $account->getNumber(),
                "currency"  => $account->getCurrencyCode()
            ],
            'amount'                => $transaction->getAmount(),
            'message'               => $transaction->getMessage(),
            'structured_message'    => $transaction->getStructuredMessage()
        ];
    }


    $result[] = $line;
}

$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();


