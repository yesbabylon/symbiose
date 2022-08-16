<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\pos\OrderPayment;
use sale\pos\Operation;


list($params, $providers) = announce([
    'description'   => "Validate a partial payment of a cashdesk order.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted payment.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron']
]);

list($context, $orm, $cron) = [$providers['context'], $providers['orm'], $providers['cron']];

// read payment object
$payment = OrderPayment::id($params['id'])
                        ->read([
                            'id', 'creator', 'status', 'total_paid', 'total_due',
                            'order_id'                  => ['session_id' => ['id', 'cashdesk_id']],
                            'order_payment_parts_ids'   => ['amount', 'payment_method']
                        ])
                        ->first();

if(!$payment) {
    throw new Exception("unknown_payment", QN_ERROR_UNKNOWN_OBJECT);
}

if($payment['status'] == 'paid') {
    throw new Exception("invalid_status", QN_ERROR_INVALID_PARAM);
}

if($payment['total_paid'] < $payment['total_due']) {
    throw new Exception("unbalanced_payment", QN_ERROR_NOT_ALLOWED);
}

$order = $payment['order_id'];
$session = $order['session_id'];

// set payment status to paid
OrderPayment::id($params['id'])->update(['status' => 'paid']);

// create cash-in operation
$cash_in = 0.0;
foreach($payment['order_payment_parts_ids'] as $pid => $part) {
    // #memo - cash part cannot be negative (but a payment can)
    if($part['payment_method'] == 'cash') {
        $cash_in += $part['amount'];
    }
}

if($cash_in > 0.0) {
    Operation::create([
        'amount'        => $cash_in,
        'type'          => 'sale',
        'user_id'       => $payment['creator'],
        'cashdesk_id'   => $session['cashdesk_id'],
        'session_id'    => $session['id']
    ]);
}

// create cash-out operation, if any
if($payment['total_paid'] > $payment['total_due']) {
    Operation::create([
        'amount'        => round($payment['total_due'] - $payment['total_paid'], 2),
        'type'          => 'sale',
        'user_id'       => $payment['creator'],
        'cashdesk_id'   => $session['cashdesk_id'],
        'session_id'    => $session['id']
    ]);
}

$context->httpResponse()
        ->status(204)
        ->send();