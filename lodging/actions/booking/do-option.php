<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\Consumption;

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
        'no_expiry' =>  [
            'description'   => 'The option will remain active without time limit.',
            'type'          => 'boolean',
            'default'       => false
        ],
        'free_rental_units' =>  [
            'description'   => 'At expiration of the option, automatically release reserved rental units, if any.',
            'type'          => 'boolean',
            'default'       => false
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
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
                      'is_price_tbc'
                   ])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'quote') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

/*
    Check booking consistency
*/

$errors = [];

// check age ranges assignments
$data = eQual::run('do', 'lodging_booking_check-ages-assignments', ['id' => $params['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'invalid_booking';
}

// check age ranges assignments
$data = eQual::run('do', 'lodging_booking_check-mealprefs-assignments', ['id' => $params['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'invalid_booking';
}

// check rental units assignments
$data = eQual::run('do', 'lodging_booking_check-units-assignments', ['id' => $params['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'invalid_booking';
}

// check list of services
$data = eQual::run('do', 'lodging_booking_check-empty', ['id' => $params['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'empty_booking';
}

// check overbooking
$data = eQual::run('do', 'lodging_booking_check-overbooking', ['id' => $params['id']]);
if(is_array($data) && count($data)) {
    $errors[] = 'overbooking_detected';
}

// raise an exception with first error (alerts should have been issued in the check controllers)
foreach($errors as $error) {
    throw new Exception($error, QN_ERROR_INVALID_PARAM);
}


/*
    Create the consumptions in order to see them in the planning (scheduled services) and to mark related rental units as booked.
    If consumptions already exist, they're removed before hand.
*/

// re-create consumptions
$orm->call(Booking::getType(), 'createConsumptions', $params['id']);

/*
    Update alerts & cron jobs
*/

$dispatch->cancel('lodging.booking.quote.blocking', 'lodging\sale\booking\Booking', $params['id']);

if($params['no_expiry'] || $booking['is_price_tbc']) {
    // set booking as never expiring
    Booking::id($params['id'])->update(['is_noexpiry' => true]);
}
else {
    // retrieve expiry delay setting
    $limit = Setting::get_value('sale', 'booking', 'option.validity', 10);

    // setup a scheduled job to set back the booking to a quote according to delay set by Setting `option.validity`
    $cron->schedule(
        "booking.option.deprecation.{$params['id']}",             // assign a reproducible unique name
        time() + $limit * 86400,                                  // remind after {sale.booking.option.validity} days (default 10 days)
        'lodging_booking_update-booking',
        [ 'id' => $params['id'], 'free_rental_units' => $params['free_rental_units'] ]
    );
}

/*
    Update booking status
*/
Booking::id($params['id'])->update(['status' => 'option']);

$context->httpResponse()
        ->status(204)
        ->send();