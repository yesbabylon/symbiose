<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Funding;
use lodging\sale\booking\Invoice;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Emit a new invoice based on a proforma.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice to emit (convert from proforma to final invoice) and create related funding.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['finance.default.user', 'booking.default.user'],
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

// emit the invoice : changing status will trigger an invoice number assignation
$invoice = Invoice::id($params['id'])->update(['status' => 'invoice'])->read(['booking_id', 'center_office_id', 'price', 'due_date'])->first();

// remove any non-paid and non-invoice remaining funding
Funding::search([ ['paid_amount', '=', 0], ['type', '=', 'installment'], ['booking_id', '=', $invoice['booking_id']] ])->delete(true);

// request funding creation
try {
    $funding = [
        'booking_id'            => $invoice['booking_id'],
        'center_office_id'      => $invoice['center_office_id'],
        'due_amount'            => $invoice['price'],
        'is_paid'               => false,
        'type'                  => 'invoice',
        'amount_share'          => 1.0,
        'order'                 => 10,
        'issue_date'            => time(),
        'due_date'              => $invoice['due_date']
    ];

    Funding::create($funding)->read(['name']);
}
catch(Exception $e) {
    // ignore duplicates (not created)
}

// update booking status

// read booking object
$booking = Booking::id($invoice['booking_id'])
                  ->read(['id', 'name', 'status'])
                  ->first();
                  
if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] == 'invoiced') {
    if($invoice['price'] < 0) {
        Booking::id($invoice['booking_id'])->update(['status' => 'credit_balance']);
    }
    else if($invoice['price'] > 0) {
        Booking::id($invoice['booking_id'])->update(['status' => 'debit_balance']);
    }
    else {
        Booking::id($invoice['booking_id'])->update(['status' => 'balanced']);
    }
}

$context->httpResponse()
        ->status(204)
        ->send();