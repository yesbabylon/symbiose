<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLineGroup;
use lodging\sale\booking\BookingLineRentalUnitAssignement;


list($params, $providers) = announce([
    'description'   => "Checks if the composition is complete for a given booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the composition is checked.',
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
$booking = Booking::id($params['id'])->read(['id', 'name', 'composition_items_ids'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

// retrieve the number of persons involved in sojourns with accomodation
$assignments = BookingLineRentalUnitAssignement::search([
        ['booking_id', '=', $params['id']],
        ['is_accomodation', '=', true]
    ])
    ->read(['booking_line_group_id'])
    ->get();

$booking_line_groups_ids = [];
if($assignments) {
    foreach($assignments as $aid => $assignment) {
        $booking_line_groups_ids[$assignment['booking_line_group_id']] = true;
    }
}

$nb_pers = 0;
if(count($booking_line_groups_ids)) {
    $groups = BookingLineGroup::ids(array_keys($booking_line_groups_ids))->read(['nb_pers'])->get();
    if($groups) {
        foreach($groups as $group) {
            $nb_pers += $group['nb_pers'];
        }
    }
}


/*
    This controller is a check: an empty response means that no alert was raised
*/

$result = [];
$httpResponse = $context->httpResponse()->status(200);

// compare with the number of lines of compositions we got so far
if(count($booking['composition_items_ids']) < $nb_pers) {
    $result[] = $params['id'];
    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.composition', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-composition', ['id' => $params['id']]);
    $httpResponse->status(qn_error_http(QN_ERROR_NOT_ALLOWED));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.composition', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();