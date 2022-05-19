<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\realestate\RentalUnit;
use lodging\sale\booking\Booking;
use lodging\sale\booking\Consumption;

list($params, $providers) = announce([
    'description'   => "Sets booking as checked in.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking on which to perform the checkin.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'no_payment' =>  [
            'description'   => 'Do not check for payments and allow to checkin even if some payments are due.',
            'type'          => 'boolean',
            'default'       => false
        ],
        'no_composition' =>  [
            'description'   => 'Do not check for composition completeness and allow to checkin without hosts personal details.',
            'type'          => 'boolean',
            'default'       => false
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


// read booking object
$booking = Booking::id($params['id'])
                  ->read(['id', 'name', 'status', 'composition_items_ids'])
                  ->first();
                  
if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    Check booking for due payments
*/

if(!$params['no_payment']) {
    $data = eQual::run('do', 'lodging_booking_check-payments', ['id' => $params['id']]);

    if(is_array($data) && count($data)) {
        // raise an exception with remaining due amount detected (an alert should have been issued in the check controller)
        throw new Exception('due_amount', QN_ERROR_NOT_ALLOWED);
    }
}


/*
    Check booking for composition
*/

if(!$params['no_composition']) {
    $data = eQual::run('do', 'lodging_booking_check-composition', ['id' => $params['id']]);

    if(is_array($data) && count($data)) {
        // raise an exception with incomplete composition detected (an alert should have been issued in the check controller)
        throw new Exception('incomplete_composition', QN_ERROR_NOT_ALLOWED);
    }
}


/*
    Adapt status for rental_units targeted by consumptions
*/

$today = time();
$consumptions = Consumption::search([
                    ['booking_id','=', $params['id']], 
                    ['type', '=', 'book'],
                    ['date', '<=', $today], 
                    ['is_accomodation', '=', true]
                ])
                ->read(['rental_unit_id'])
                ->get();

if($consumptions) {
    foreach($consumptions as $cid => $consumption) {
        lodging_booking_do_checkin_mark_busy($orm, $consumption['rental_unit_id']);
    }
}


/*
    Update booking status
*/

Booking::id($params['id'])->update(['status' => 'checkedin']);


$context->httpResponse()
        ->status(204)
        ->send();


/**
 * Recursively sets all involved rental units to their related occupation status (busy_full or busy_part).
 * 
 */
function lodging_booking_do_checkin_mark_busy($orm, $rental_unit_id, $is_parent = false, $is_child = false) {
    if($is_parent) {
        $rental_unit = RentalUnit::id($rental_unit_id)->read(['can_partial_rent', 'parent_id'])->first();
        if($rental_unit['can_partial_rent']) {
            $orm->write('lodging\realestate\RentalUnit', $rental_unit_id, ['status' => 'busy_part']);
        }
        else {
            $orm->write('lodging\realestate\RentalUnit', $rental_unit_id, ['status' => 'busy_full']);
        }
        if($rental_unit['parent_id']) {
            lodging_booking_do_checkin_mark_busy($orm, $rental_unit['parent_id'], true);
        }
    }
    else if($is_child) {
        $orm->write('lodging\realestate\RentalUnit', $rental_unit_id, ['status' => 'busy_full']);        
        $rental_unit = RentalUnit::id($rental_unit_id)->read(['children_ids'])->first();
        if($rental_unit['children_ids'] && count($rental_unit['children_ids'])) {
            foreach($rental_unit['children_ids'] as $child_id) {
                lodging_booking_do_checkin_mark_busy($orm, $child_id, false, true);
            }
        }
    }
    else {
        $orm->write('lodging\realestate\RentalUnit', $rental_unit_id, ['status' => 'busy_full']);
        $rental_unit = RentalUnit::id($rental_unit_id)->read(['parent_id', 'children_ids'])->first();
        if($rental_unit['children_ids'] && count($rental_unit['children_ids'])) {
            foreach($rental_unit['children_ids'] as $child_id) {
                lodging_booking_do_checkin_mark_busy($orm, $child_id, false, true);
            }
        }
        if($rental_unit['parent_id']) {
            lodging_booking_do_checkin_mark_busy($orm, $rental_unit['parent_id'], true);
        }
    }
}