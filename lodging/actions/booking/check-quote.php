<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\Consumption;

list($params, $providers) = announce([
    'description'   => "Checks if a quote is blocking one or more rental unit(s).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking the check against unit blocking.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $dispatch) = [ $providers['context'], $providers['dispatch']];

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'status'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    This controller is a check: an empty response means that no alert was raised
*/
$result = [];
$httpResponse = $context->httpResponse()->status(200);

$consumptions = Consumption::search([['booking_id', '=', $params['id']], ['is_rental_unit', '=', true]])->get();

if($booking['status'] == 'quote' && count($consumptions)) {
    $result[] = $booking['id'];

    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.quote.blocking', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-quote', ['id' => $params['id']]);

    $httpResponse->status(qn_error_http(QN_ERROR_MISSING_PARAM));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.quote.blocking', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();