<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Consumption;
use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLineRentalUnitAssignement;
use lodging\realestate\RentalUnit;
use equal\orm\Domain;


list($params, $providers) = announce([
    'description'   => "Retrieve the list of available rental units for a given center, during a specific timerange.",
    'params'        => [
        'center_id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'required'      => true
        ],
        'booking_id' =>  [
            'description'   => 'Identifier of the related product.',
            'type'          => 'integer'
        ],
        'booking_line_id' =>  [
            'description'   => 'Specific line for which availability list is requested.',
            'type'          => 'integer'
        ],
        'product_id' =>  [
            'description'   => 'Identifier of the related product.',
            'type'          => 'integer',
            'required'      => true
        ],
        'date_from' =>  [
            'description'   => 'Date of the first day of the sojourn.',
            'type'          => 'date',
            'required'      => true
        ],
        'date_to' =>  [
            'description'   => 'Date of the last day of the sojourn.',
            'type'          => 'date',
            'required'      => true
        ],
        'domain' =>  [
            'description'   => 'Filter to apply on rental units names.',
            'type'          => 'array',
            'default'       => []
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron']
]);


list($context, $orm, $cron) = [$providers['context'], $providers['orm'], $providers['cron']];

/*
    Create the consumptions in order to see them in the planning (scheduled services) and to mark related rental units as booked.
*/

$rental_units_ids = Consumption::_getAvailableRentalUnits($orm, $params['center_id'], $params['product_id'], $params['date_from'], $params['date_to']);

// append rental units from own booking consumptions (use case: come and go between 'draft' and 'option', where units are already attached to consumptions)
if(isset($params['booking_id'])) {
    $booking = Booking::id($params['booking_id'])->read(['consumptions_ids' => ['rental_unit_id']])->first();
    if($booking) {
        foreach($booking['consumptions_ids'] as $consumption) {
            $rental_units_ids[] = $consumption['rental_unit_id'];
        }
    }

    // remove units from other lines
    if($params['booking_line_id']) {
        $assignments = BookingLineRentalUnitAssignement::search(['booking_id', '=', $params['booking_id']])->read(['rental_unit_id', 'booking_line_id'])->get();
        $used_rental_units_ids = [];
        foreach($assignments as $assignment) {
            if($assignment['booking_line_id'] != $params['booking_line_id']) {
                $used_rental_units_ids[] = $assignment['rental_unit_id'];
            }
        }
        $rental_units_ids = array_diff($rental_units_ids, $used_rental_units_ids);
    }
}


$rental_units = RentalUnit::ids($rental_units_ids)->read(['id', 'name', 'capacity'])->adapt('txt')->get(true);

$result = [];

$domain = new Domain($params['domain']);

// filter results
foreach($rental_units as $index => $rental_unit) {
    if($domain->evaluate($rental_unit)) {
        $result[] = $rental_unit;
    }
}

$context->httpResponse()
        ->body($result)
        ->send();