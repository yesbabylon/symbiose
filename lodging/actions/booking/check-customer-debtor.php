<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;


list($params, $providers) = announce([
    'description'   => "Checks that the customer of a booking has no unbalanced booking remaining.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the customer must be checked.',
            'type'          => 'integer',
            'required'      => true
        ],
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
$booking = Booking::id($params['id'])->read(['id', 'name', 'center_office_id', 'customer_id'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}


$bookings_ids = Booking::search([ ['customer_id', '=', $booking['customer_id']], ['status', 'in', ['invoiced', 'debit_balance']] ])->ids();

/*
    This controller is a check: an empty response means that no alert was raised
*/

$result = [];
$httpResponse = $context->httpResponse()->status(200);

// compare with the number of lines of compositions we got so far
if(count($bookings_ids)) {
    $bookings = Booking::ids($bookings_ids)->read(['id', 'name'])->get();
    $links = [];

    foreach($bookings as $booking_id => $booking) {
        $links[] = "[{$booking['name']}](/booking/#/booking/{$booking_id})";
        $result[] = $booking_id;
    }
    // raise a notice
    $dispatch->dispatch('lodging.booking.debtor_customer', 'lodging\sale\booking\Booking', $params['id'], 'notice', null, [], $links,null,$booking['center_office_id']);
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.debtor_customer', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();