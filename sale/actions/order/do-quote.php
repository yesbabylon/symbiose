<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;
use sale\order\Contract;

list($params, $providers) = eQual::announce([
    'description'   => "Reverts a order to 'quote' status.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted order.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['order.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context                  $context
 */
$context = $providers['context'];


$order = Order::id($params['id'])
    ->read([
        'id',
        'status',
        'contracts_ids',
        'fundings_ids'
    ])
    ->first(true);


if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if($order['status'] == 'quote') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

eQual::run('do', 'sale_order_funding_update', ['ids' =>  array_values($order['fundings_ids'])]);

Contract::ids($order['contracts_ids'])->update(['status' => 'cancelled']);

Order::id($params['id'])->update(['status' => 'quote','has_contract' => false]);

$context->httpResponse()
        ->status(204)
        ->send();