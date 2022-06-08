<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\booking\Consumption;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Checks if there is at least one service attached to the given booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking the check against emptyness.',
            'type'          => 'integer',
            'required'      => true
        ],

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $orm, $auth, $dispatch) = [ $providers['context'], $providers['orm'], $providers['auth'], $providers['dispatch']];

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'booking_lines_ids', 'price'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    This controller is a check: an empty response means that no alert was raised
*/
$result = [];
$httpResponse = $context->httpResponse()->status(200);

if(!count($booking['booking_lines_ids']) || $booking['price'] == 0) {
    $result[] = $booking['id'];

    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.empty', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-empty', ['id' => $params['id']]);

    $httpResponse->status(qn_error_http(QN_ERROR_MISSING_PARAM));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.empty', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();