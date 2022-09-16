<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Consumption;
use lodging\sale\booking\Booking;
use lodging\realestate\RentalUnit;
use equal\orm\Domain;
use lodging\sale\booking\BookingLineGroup;

list($params, $providers) = announce([
    'description'   => "Retrieve the list of available rental units for a given sojourn, during a specific timerange.",
    'params'        => [
        'booking_line_group_id' =>  [
            'description'   => 'Specific sojourn for which is made the request.',
            'type'          => 'integer'
        ],
        'product_model_id' =>  [
            'description'   => 'Specific product model for which a matching rental unit list is requested.',
            'type'          => 'integer'
        ],
        'domain' =>  [
            'description'   => 'Dommain for additional filtering.',
            'type'          => 'array',
            'default'       => []
        ],
    ],
    'access' => [
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);


list($context, $orm) = [$providers['context'], $providers['orm']];

$result = [];

// retrieve sojourn data
$sojourn = BookingLineGroup::id($params['booking_line_group_id'])
    ->read([
        'booking_id' => [
            'id',
            'center_id'
        ],
        'date_from',
        'date_to',
        'time_from',
        'time_to'
    ])
    ->first();

if($sojourn) {
    $date_from = $sojourn['date_from'] + $sojourn['time_from'];
    $date_to = $sojourn['date_to'] + $sojourn['time_to'];
    // retrieve available rental units based on schedule and product_id
    $rental_units_ids = Consumption::getAvailableRentalUnits($orm, $sojourn['booking_id']['center_id'], $params['product_model_id'], $date_from, $date_to);

    // append rental units from own booking consumptions (use case: come and go between 'draft' and 'option', where units are already attached to consumptions)

    // #memo - this leads to an edge case: quote -> option -> quote (without releasing the consumptions)
    // 1) update nb_pers or time_from (list is not accurate and might return units that are not free)
    // 2) if another booking has booked the units in the meanwhile
    $booking = Booking::id($sojourn['booking_id']['id'])
        ->read(['consumptions_ids' => ['rental_unit_id']])
        ->first();

    if($booking) {
        foreach($booking['consumptions_ids'] as $consumption) {
            // $rental_units_ids[] = $consumption['rental_unit_id'];
        }
    }

    // #memo - we cannot remove units already assigned in same booking since the allocation of an accomodation might be split on several age ranges (ex: room for 5 pers. with 2 adults and 3 children)

    $rental_units = RentalUnit::ids($rental_units_ids)
        ->read(['id', 'name', 'capacity'])
        ->adapt('txt')
        ->get(true);

    $domain = new Domain($params['domain']);

    // filter results
    foreach($rental_units as $index => $rental_unit) {
        if($domain->evaluate($rental_unit)) {
            $result[] = $rental_unit;
        }
    }
}

$context->httpResponse()
        ->body($result)
        ->send();