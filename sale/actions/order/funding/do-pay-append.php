<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Funding;
use sale\order\Payment;

list($params, $providers) = eQual::announce([
    'description'   => "Create a manual payment to complete the payments of a funding and mark it as paid.",
    'help'          => "This action is intended for payment with bank card only. Manual payments can be undone while the order is not fully balanced (and invoiced).",
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
            ->read(['id', 'order_id' => ['id', 'customer_id'], 'invoice_id','is_paid', 'paid_amount', 'due_amount'])
            ->first(true);

if(!$funding) {
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if($funding['is_paid']) {
    throw new Exception("funding_already_paid", QN_ERROR_INVALID_PARAM);
}

$sign = ($funding['due_amount'] >= 0)? 1 : -1;
$remaining_amount = abs($funding['due_amount']) - abs($funding['paid_amount']);

if($remaining_amount <= 0) {
    throw new Exception("nothing_to_pay", QN_ERROR_INVALID_PARAM);
}

Payment::create([
    'order_id'          => $funding['order_id']['id'],
    'customer_id'       => $funding['order_id']['customer_id'],
    'amount'            => $sign * $remaining_amount,
    'payment_origin'    => 'cashdesk',
    'payment_method'    => 'bank_card'
])
->update([
    'funding_id'        => $funding['id']
]);


Funding::id($funding['id'])
    ->update([
        'paid_amount'    => null,
        'is_paid'        => null
    ])
    ->read(['is_paid', 'paid_amount']);

$context->httpResponse()
        ->status(205)
        ->send();