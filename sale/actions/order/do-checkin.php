<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Order;
use sale\order\Contract;

list($params, $providers) = announce([
    'description'   => "Sets order as checked in.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order on which to perform the checkin.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'contract_sign' => [
            'description'   => 'Mark the contract as sent and signed if it has not already been done.',
            'type'          => 'boolean',
            'default'       => true
        ],
        'no_payment' =>  [
            'description'   => 'Do not check for payments and allow to checkin even if some payments are due.',
            'type'          => 'boolean',
            'default'       => true
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

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$order = Order::id($params['id'])
                  ->read(['id','status', 'has_contract', 'contracts_ids'])
                  ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($order['status'],['confirmed', 'validated'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

$errors = [];

if ($params['contract_sign']) {

    $contract_id = array_shift($order['contracts_ids']);
    $contract = Contract::id($contract_id)->read(['id', 'status'])->first();

    if ($contract['status'] != 'signed'){

        if($contract['status'] == 'pending'){
            $data = eQual::run('do', 'sale_contract_sent', ['id' => $contract['id']]);
        }
        $data = eQual::run('do', 'sale_contract_signed', ['id' => $contract['id']]);

        if(is_array($data) && count($data)) {
            $errors[] = 'contract_sign_failed';
        }
    }

}
else {
    $data = eQual::run('do', 'sale_order_check-contract', ['id' => $order['id']]);
    if(is_array($data) && count($data)) {
        $errors[] = 'unsigned_contract';
    }
}

if(!$params['no_payment']) {
    $data = eQual::run('do', 'sale_order_check-payments', ['id' => $order['id']]);
    if(is_array($data) && count($data)) {
        $errors[] = 'due_amount';
    }
}
foreach($errors as $error) {
    throw new Exception($error, QN_ERROR_INVALID_PARAM);
}

Order::id($order['id'])->update(['status' => 'checkedin']);

$context->httpResponse()
        ->status(204)
        ->send();