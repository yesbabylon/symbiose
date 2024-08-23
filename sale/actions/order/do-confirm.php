<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Order;
use sale\order\Contract;


list($params, $providers) = announce([
    'description'   => "Sets order as confirmed, creates contract and generates payment plan.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order to mark as confirmed.',
            'type'          => 'integer',
            'min'           => 1,
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
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];


$order = Order::id($params['id'])
    ->read([
        'id',
        'status',
        'contracts_ids'
    ])
    ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if($order['status'] != 'option') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

Contract::ids($order['contracts_ids'])->update(['status' => 'cancelled']);

Order::id($order['id'])->update(['status' => 'confirmed']);

$context->httpResponse()
        ->status(204)
        ->send();