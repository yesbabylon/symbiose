<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;
use sale\order\Funding;
use sale\order\Payment;

list($params, $providers) = eQual::announce([
    'description'   => "Remove the manual payment attached to the funding, if any, and unmark funding as paid.",
    'help'          => "Manual payments can be undone while the order is not fully balanced (and invoiced).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted funding.',
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
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $om
 */
list($context, $om) = [ $providers['context'], $providers['orm'] ];

$funding = Funding::id($params['id'])
            ->read(['id', 'order_id' => ['id', 'status'], 'invoice_id','is_paid', 'paid_amount', 'due_amount'])
            ->first(true);

if(!$funding) {
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$funding['is_paid']) {
    throw new Exception("funding_not_paid", QN_ERROR_INVALID_PARAM);
}

if($funding['order_id']['status'] == 'balanced') {
    throw new Exception("order_balanced", QN_ERROR_INVALID_PARAM);
}

$payments = Payment::search(['funding_id', '=', $funding['id']])->update(['status' => 'pending'])->delete(true);

Funding::id($params['id'])
    ->update(['paid_amount' => null, 'is_paid' => null])
    ->read(['is_paid', 'paid_amount']);

Order::updateStatusFromFundings((array) $funding['order_id']['id']);

$context->httpResponse()
        ->status(205)
        ->send();