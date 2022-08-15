<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Consumption;

use core\Task;

list($params, $providers) = announce([
    'description'   => "Revert a booking to 'quote' status. By default, rental units will remain reserved for 24h.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'free_rental_units' =>  [
            'description'   => 'Flag for marking reserved rental units to be release immediately, if any.',
            'type'          => 'boolean',
            'default'       => false
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
    'providers'     => ['context', 'orm', 'cron', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $orm, $cron, $dispatch) = [$providers['context'], $providers['orm'], $providers['cron'], $providers['dispatch']];

// read booking object
$booking = Booking::id($params['id'])
                  ->read(['id', 'name', 'status', 'contracts_ids', 'booking_lines_ids', 'fundings_ids' => ['id', 'is_paid']])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'quote') {

    /*
        Update alerts & cron jobs
    */

    // remove messages about readyness for this booking, if any
    $dispatch->cancel('lodging.booking.ready', 'lodging\sale\booking\Booking', $params['id']);

    // remove existing CRON tasks for reverting the booking to quote
    $cron->cancel("booking.option.deprecation.{$params['id']}");

    /*
        Update booking
    */

    // set booking status to quote
    Booking::id($params['id'])->update(['status' => 'quote']);

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
    // mark contracts as expired
    // #memo - generated contracts are kept for history (we never delete them)
    Contract::ids($booking['contracts_ids'])->update(['status' => 'cancelled']);
    // remove consumptions if requested (link & part)
    if($params['free_rental_units']) {
        Consumption::search(['booking_id', '=', $params['id']])->delete(true);
    }
    // mark lines as not 'invoiced' (waiting for payment)
    BookingLine::ids($booking['booking_lines_ids'])->update(['is_contractual' => false]);
    // mark booking as non-having contract, remove non-paid fundings and remove existing consumptions
    Booking::id($params['id'])->update(['has_contract' => false, 'fundings_ids' => $fundings_ids_to_remove]);
}

$context->httpResponse()
        ->status(204)
        ->send();