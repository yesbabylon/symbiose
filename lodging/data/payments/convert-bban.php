<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\BankStatement;

list($params, $providers) = announce([
    'description'   => "Returns the IBAN format of a bank account as provided by belgian Bank statements (CODA).",
    'params'        => [
        'bban' =>  [
            'description'   => 'BBAN account number.',
            'type'          => 'string',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'private'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);


list($context, $orm) = [$providers['context'], $providers['orm']];


$iban = BankStatement::convertBbanToIban($params['bban']);


$context->httpResponse()
        ->status(200)
        ->body(['result' => $iban])
        ->send();