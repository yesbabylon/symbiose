<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;
use lodging\sale\booking\Consumption;

use core\Task;

list($params, $providers) = announce([
    'description'   => "Revert a booking to 'quote' status: booking is visible but no rental units are reserved.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron']     
]);

list($context, $orm, $cron) = [$providers['context'], $providers['orm'], $providers['cron']];

// read booking object
$booking = Booking::id($params['id'])
                  ->read(['id', 'name', 'status', 'contracts_ids', 'booking_lines_ids', 'fundings_ids' => ['id', 'is_paid']])
                  ->first();
                  
if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'quote') {

    // set booking status to quote
    Booking::id($params['id'])->update(['status' => 'quote']);

    // remove existing CRON tasks for reverting the booking to quote
    $cron->cancel("booking.option.deprecation.{$params['id']}");

    // #memo - generated contracts are kept for history (we never delete these)

    // mark contracts as expired    
    Contract::ids($booking['contracts_ids'])->update(['status' => 'cancelled']);

    // Update booking

    $fundings_ids_to_remove = [];
    foreach($booking['fundings_ids'] as $fid => $funding) {
        if($funding['type'] == 'invoice') { 
            // once emitted, we cannot remove an invoice without creating a credit note
            continue;
        }
        if(!$funding['is_paid']) {
            $fundings_ids_to_remove[] = "-$fid";
        }
    }
    // remove consumptions + link & part
    $consumptions_ids_to_remove = Consumption::search(['booking_id', '=', $params['id']])->delete(true);

    // mark lines as not 'invoiced' (waiting for payment)
    BookingLine::ids($booking['booking_lines_ids'])->update(['is_contractual' => false]);
    // mark booking as non-having contract, remove non-paid fundings and remove existing consumptions
    Booking::id($params['id'])->update(['has_contract' => false, 'fundings_ids' => $fundings_ids_to_remove]);
}

$context->httpResponse()
        ->status(200)
        ->body([])
        ->send();