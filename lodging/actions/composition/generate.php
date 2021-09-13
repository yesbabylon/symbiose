<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\realestate\RentalUnit;
use sale\booking\Composition;
use sale\booking\CompositionItem;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Generate the composition (hosts listing) for a given booking. If a composition already exists, it is reset.",
    'params'        => [
        'booking_id' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();


// read groups and nb_pers from the targeted booking object, and subsequent lines
$booking = Booking::id($params['booking_id'])
->read([
    'booking_lines_groups_ids' => [
        'nb_pers',
        'booking_lines_ids' => [
            'id', 'qty_accounting_method', 'rental_unit_id'
        ]
    ]
])
->first();

if(!$booking) {
    throw new Exception('unknown_booking', QN_ERROR_INVALID_PARAM);
}


$auth->su();
    // remove any existing composition (and related composition items with cascade deletion)
    Composition::search(['booking_id', '=', $booking['id']])->delete(true);
    // create a new composition attached to current booking
    $composition_id = (Composition::create(['booking_id' => $booking['id']])->ids())[0];
    // update booking accordingly (o2o relation)
    Booking::id($booking['id'])->update(['composition_id' => $composition_id]);
$auth->su($user_id);


foreach($booking['booking_lines_groups_ids'] as $group) {
    $nb_pers = $group['nb_pers'];
    $remainder = $nb_pers;

    // find all rental units, based on involved booking_lines
    $rental_units_map = [];
    foreach($group['booking_lines_ids'] as $line) {
        if($line['qty_accounting_method'] == 'accomodation') {
            $rental_units_map[$line['rental_unit_id']] = true;
        }
    }
    // get unique ids of involved rental units
    $rental_units_ids = array_keys($rental_units_map);
    // retrieve rental units capacities
    $rental_units = RentalUnit::ids($rental_units_ids)
    ->read(['id', 'capacity'])
    ->get();
    // sort rental units by ascending capacities
    usort($rental_units, function($a, $b) {
        return $a['capacity'] - $b['capacity'];
    });

    $total_capacity = array_reduce($rental_units, function($total, $unit) {return $total + $unit['capacity'];});
    $last_index = count($rental_units) - 1;

    foreach($rental_units as $index => $unit) {
        // to each UL, assign ceil(nb_pers*cap/cap_total)
        if($index < $last_index) {
            $capacity = $unit['capacity'];
            $assigned = ceil($nb_pers*$capacity/$total_capacity);
            $remainder -= $assigned;
        }
        //and assign the remainder to the last UL
        else {
            $assigned = $remainder;
        }            
        for($i = 0; $i < $assigned; ++$i) {
            CompositionItem::create([
                'composition_id' => $composition_id,
                'rental_unit_id' => $unit['id']
            ]);
        }
    }
}


$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();