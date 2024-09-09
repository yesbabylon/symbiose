<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;


list($params, $providers) = announce([
    'description'	=>	"Update a order status after balance invoice has been emitted.",
    'params' 		=>	[
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
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

/**
 * @var \equal\php\Context          $context
 */
$context = $providers['context'];

$order = Order::id($params['id'])
                  ->read(['id', 'status', 'is_invoiced'])
                  ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($order['status'], ['invoiced', 'credit_balance', 'debit_balance'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

Order::updateStatusFromFundings((array) $order['id']);

$context->httpResponse()
        ->status(204)
        ->send();