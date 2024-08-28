<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;
use sale\order\Invoice;

list($params, $providers) = announce([
    'description'   => "Sets order as checked out.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order on which to perform the checkout.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['order.default.user']
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
 */
list($context, $orm) = [$providers['context'], $providers['orm']];


$order = Order::id($params['id'])->read(['id'])->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$balance_invoice = Invoice::search([['order_id', '=', $order['id']], ['invoice_type', '=', 'invoice'], ['status', '=', 'invoice']])->read(['id'])->first(true);

if($balance_invoice) {
    throw new Exception("emitted_balance_invoice", QN_ERROR_INVALID_PARAM);
}

Order::id($order['id'])->update(['status' => 'checkedout']);

$context->httpResponse()
        ->status(204)
        ->send();