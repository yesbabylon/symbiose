<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Booking;

use core\setting\Setting;

list($params, $providers) = announce([
    'description'   => "Update the status of given booking to 'option'. Related consumptions are added to the planning. Auto-deprecation of the option is scheduled according to setting `sale.booking.option.validity`.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        // this must remain synched with field definition Booking::cancellation_reason
        'no_expiry' =>  [
            'description'   => 'Rental units are blocked and the option will remain without time limit.',
            'type'          => 'boolean',
            'default'       => false
        ]
    ],
    'access' => [
        'visibility'        => 'public', // 'public' (default) or 'private' (can be invoked by CLI only)		
        'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron'] 
]);


list($context, $orm, $cron) = [$providers['context'], $providers['orm'], $providers['cron']];


/*
    Check if rental_units assigned to the booking are still available at given dates (otherwise, generate an error).

    We perform a 2-pass operation:
        1. First, we create the consumptions
        2. Second, we try to detect an overbooking for the current booking (based on booking_id)
*/

// read booking object
$booking = Booking::id($params['id'])
                  ->read([
                      'status',
                      'booking_lines_ids',
                      'is_price_tbc'
                   ])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'quote') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

if(!count($booking['booking_lines_ids'])) {
    throw new Exception("empty_booking", QN_ERROR_MISSING_PARAM);
}


/*
    Check booking consistency
*/


$data = eQual::run('do', 'lodging_booking_check-overbooking', ['id' => $params['id']]);

if(is_array($data) && count($data)) {
    // raise an exception with overbooking_detected (an alert should have been issued in the check controller)
    throw new Exception('overbooking_detected', QN_ERROR_CONFLICT_OBJECT);
}

/*
    Create the consumptions in order to see them in the planning (scheduled services) and to mark related rental units as booked.
*/

BookingLine::_createConsumptions($orm, $booking['booking_lines_ids'], DEFAULT_LANG);


/*
    Update booking status
*/



/*
    Setup a scheduled job to set back the booking to a quote according to delay set by Setting `option.validity`
*/
if($params['no_expiry']) {
    // set booking as never expiring
    Booking::id($params['id'])->update(['is_noexpiry' => true]);        
}
else {
    if($booking['is_price_tbc']) {
        // do not schedule deprecation, and set booking as never expiring
        Booking::id($params['id'])->update(['is_noexpiry' => true]);        
    }
    else {
        // retrieve expiry delay setting
        $limit = Setting::get_value('sale', 'booking', 'option.validity', 10);

        // add a task to the CRON
        $cron->schedule(
            "booking.option.deprecation.{$params['id']}",             // assign a reproducible unique name
            time() + $limit * 86400,                                  // remind after {sale.booking.option.validity} days (default 10 days)
            'lodging_booking_quote',
            [ 'id' => $params['id'] ]
        );
    }
}

Booking::id($params['id'])->update(['status' => 'option']);

$context->httpResponse()
        ->status(204)
        ->send();