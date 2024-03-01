<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\pos\OrderPayment;

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
                            'id', 'creator', 'status', 'total_paid', 'total_due'
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

// set payment status to paid
OrderPayment::id($params['id'])->update(['status' => 'paid']);

$context->httpResponse()
        ->status(204)
        ->send();
