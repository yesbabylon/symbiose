<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;


list($params, $providers) = announce([
    'description'   => "Checks the history of the customer of a booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the customer must be checked.',
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
$booking = Booking::id($params['id'])->read(['id', 'name', 'center_office_id', 'customer_identity_id' => ['flag_latepayer', 'flag_damage', 'flag_nuisance']])->first(true);

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    This controller is a check: an empty response means that no alert was raised
*/

$result = [];
$httpResponse = $context->httpResponse()->status(200);

$identity = $booking['customer_identity_id'];

if(isset($identity['flag_latepayer']) && $identity['flag_latepayer']) {
    // raise a notice
    $dispatch->dispatch('lodging.booking.latepayer_customer', 'lodging\sale\booking\Booking', $params['id'], 'notice', null, [], [], null, $booking['center_office_id']);
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.latepayer_customer', 'lodging\sale\booking\Booking', $params['id']);
}

if(isset($identity['flag_damage']) && $identity['flag_damage']) {
    // raise a notice
    $dispatch->dispatch('lodging.booking.damage_customer', 'lodging\sale\booking\Booking', $params['id'], 'notice');
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.damage_customer', 'lodging\sale\booking\Booking', $params['id']);
}

if(isset($identity['flag_nuisance']) && $identity['flag_nuisance']) {
    // raise a notice
    $dispatch->dispatch('lodging.booking.nuisance_customer', 'lodging\sale\booking\Booking', $params['id'], 'notice');
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.nuisance_customer', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();