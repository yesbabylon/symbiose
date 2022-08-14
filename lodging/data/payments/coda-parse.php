<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use Codelicious\Coda\Parser;

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

// function for converting BBAN to IBAN
$lodging_payments_import_getIbanFromBban = function ($bban) {
    $result = '';

    $country_code = 'BE';

    $code_alpha = $country_code;
    $code_num = '';
    for($i = 0; $i < strlen($code_alpha); ++$i) {
        $letter = substr($code_alpha, $i, 1);
        $order = ord($letter) - ord('A');
        $code_num .= '1'.$order;
    }
    // account number has IBAN format
    if(substr($bban, 0, 2) == $country_code) {
        $result = $bban;
    }
    // convert to IBAN
    else {
        $check_digits = substr($bban, -2);
        $dummy = intval($check_digits.$check_digits.$code_num.'00');
        $control = 98 - ($dummy % 97);
        $result = sprintf("BE%s%s", $control, $bban);
    }
    return $result;
};



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
        "iban"      => $lodging_payments_import_getIbanFromBban($account->getNumber()),
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


