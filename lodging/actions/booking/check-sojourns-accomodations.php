<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\SojournProductModelRentalUnitAssignement;
use lodging\sale\catalog\ProductModel;

list($params, $providers) = announce([
    'description'   => "Checks that the sojounrs (services group) of a booking don't use the same accomodations at the same dates.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the assignments are checked.',
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
$booking = Booking::id($params['id'])
    ->read([
        'id',
        'name',
        'center_office_id',
        'booking_lines_groups_ids' => [
            'date_from',
            'date_to',
            'rental_unit_assignments_ids' => [
                'rental_unit_id',
                'is_accomodation'
            ]
        ]
    ])
    ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}


$collision = false;

$rental_units_map = [];
foreach($booking['booking_lines_groups_ids'] as $group) {
    for($day = $group['date_from']; $day < $group['date_to']; $day += 86400) {
        $date_index = date('Y-m-d', $day);
        foreach($group['rental_unit_assignments_ids'] as $assignment) {
            if(!$assignment['is_accomodation']) {
                continue;
            }
            if(isset($rental_units_map[$assignment['rental_unit_id']][$date_index])) {
                $collision = true;
                break 3;
            }
            else {
                if(!isset($rental_units_map[$assignment['rental_unit_id']])) {
                    $rental_units_map[$assignment['rental_unit_id']] = [];
                }
                $rental_units_map[$assignment['rental_unit_id']][$date_index] = true;
            }
        }
    }
}


/*
    This controller is a check: an empty response means that no alert was raised
*/

$result = [];
$httpResponse = $context->httpResponse()->status(200);


if($collision) {
    $result[] = $params['id'];
    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.sojourns_accomodations', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-sojourns-accomodations', ['id' => $params['id']],[],null,$booking['center_office_id']);
    $httpResponse->status(qn_error_http(QN_ERROR_NOT_ALLOWED));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.sojourns_accomodations', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();