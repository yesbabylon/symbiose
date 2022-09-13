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
    'description'   => "Emit a new invoice from an existing proforma and update related booking, if necessary.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice to emit.',
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
    'providers'     => ['context', 'orm', 'cron', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $cron, $auth) = [$providers['context'], $providers['orm'], $providers['cron'], $providers['auth']];

// emit the invoice (changing status will trigger an invoice number assignation)
$invoice = Invoice::id($params['id'])
    ->update(['status' => 'invoice'])
    ->read(['id', 'booking_id', 'funding_id', 'center_office_id', 'price', 'due_date'])
    ->first();

// if invoice do not yet relate to a funding: it is a final invoice (balance)
if(is_null($invoice['funding_id'])) {
    try {
        // update booking status
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
        // compute the amount share, based on the existing fundings for the booking
        $booking = Booking::id($invoice['booking_id'])->read(['fundings_ids' => ['amount_share']])->first();
        $amount_share = 1.0;
        foreach($booking['fundings_ids'] as $fid => $funding) {
            $amount_share -= $funding['amount_share'];
        }
        // create a new funding relating to the invoice
        $funding = [
            'description'           => 'Facture de solde',
            'booking_id'            => $invoice['booking_id'],
            'invoice_id'            => $invoice['id'],
            'center_office_id'      => $invoice['center_office_id'],
            'due_amount'            => $invoice['price'],
            'is_paid'               => false,
            'type'                  => 'invoice',
            'amount_share'          => $amount_share,
            'order'                 => 10,
            'issue_date'            => time(),
            'due_date'              => $invoice['due_date']
        ];
        $new_funding = Funding::create($funding)->read(['id', 'name'])->first();
    }
    catch(Exception $e) {
        // ignore duplicates (not created)
    }
}

$context->httpResponse()
        ->status(204)
        ->send();