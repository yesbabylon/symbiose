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
    'description'   => "Returns a list of bookings that collide with one or more consumptions of the given booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking the check against overbooking.',
            'type'          => 'integer',
            'required'      => true
        ],

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth', 'report']
]);

list($context, $orm, $auth, $report) = [ $providers['context'], $providers['orm'], $providers['auth'], $providers['report']];

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'booking_lines_ids'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}


/*
    Get in-memory consumptions resulting from the booking lines, in order to check booking consumptions against existing consumptions.
*/

$consumptions = BookingLine::_getResultingConsumptions($orm, $booking['booking_lines_ids'], DEFAULT_LANG);

// filter to keep only is_rental_unit
$consumptions = array_filter($consumptions, function($a) {
    return $a['is_rental_unit'];
});

// sort ascending on date
usort($consumptions, function($a, $b) {
    return ($a['date'] < $b['date'])?-1:1;
});

// map of consumptions by rental_unit (ordered on date)
$consumptions_map = [];

foreach($consumptions as $consumption) {
    $rental_unit_id = $consumption['rental_unit_id'];
    $booking_line_group_id = $consumption['booking_line_group_id'];

    if($rental_unit_id <= 0) continue;
    if(!isset($consumptions_map[$booking_line_group_id])) {
        $consumptions_map[$booking_line_group_id] = [];
    }
    
    if(!isset($consumptions_map[$booking_line_group_id][$rental_unit_id])) {
        $consumptions_map[$booking_line_group_id][$rental_unit_id] = [];
    }
    $consumptions_map[$booking_line_group_id][$rental_unit_id][] = $consumption;
}

/*
    Detect collisions and keep involved bookings ids.
*/

// map of colliding bookings
$colliding_bookings_map = [];

foreach($consumptions_map as $booking_line_group_id => $rental_units) {

    foreach($rental_units as $rental_unit_id => $consumptions) {

        // get the date of the first consommation => date_from ; and the date of last consommation => date_to
        $len = count($consumptions);
        $first = $consumptions[0];
        $last = $consumptions[$len-1];
        $date_from = $first['date'];
        $date_to = $last['date'];

        // look for other consumptionS (not in booking_line_group_id) assigned to the same rental_unit with date >= date_from AND date <= date_to
        $colliding_ids = Consumption::search([ 
            ['booking_line_group_id', '<>', $booking_line_group_id], 
            ['rental_unit_id', '=', $rental_unit_id], 
            ['date', '>=', $date_from], 
            ['date', '<=', $date_to]
        ])->ids();

        if($colliding_ids && count($colliding_ids)) {
            // filter resulting collisions
            $colliding_consumptions = Consumption::ids($colliding_ids)->read(['booking_id', 'date', 'schedule_from', 'schedule_to'])->get();

            foreach($colliding_consumptions as $cid => $collision) {
                if($collision['date'] == $first['date']) {
                    if($collision['schedule_to'] < $first['schedule_from']) {
                        unset($colliding_consumptions[$cid]);
                        continue;
                    }
                }
                if($collision['date'] == $last['date']) {
                    if($collision['schedule_from'] > $last['schedule_to']) {
                        unset($colliding_consumptions[$cid]);
                    }
                }
            }

            // collision array is not empty : surbooking !  
            if(count($colliding_consumptions)) {

                foreach($colliding_consumptions as $cid => $consumption) {
                    $colliding_bookings_map[$consumption['booking_id']] = true;
                }
            }

        }

    }
}

$colliding_bookings_ids = array_keys($colliding_bookings_map);

$result = [];
$httpResponse = $context->httpResponse()->status(200);

if(count($colliding_bookings_ids)) {
    $bookings = Booking::ids($colliding_bookings_ids)->read(['id', 'name'])->get(true);
    /*
        This controller is a check.
        By convention we return a response allowing to retrieve objects in the list OR providing a message.
    */
    foreach($bookings as $booking) {
        $result[] = [
            'type'          => 'object',                        // 'object' or 'message'
        //  'message'       => 'warning message',
            'object_class'  => 'lodging\sale\booking\Booking',            
            'object_id'     => $booking['id'],
            'object_name'   => $booking['name']
        ];
    }
    $httpResponse->status(qn_error_http(QN_ERROR_CONFLICT_OBJECT));
}

$httpResponse->body($result)
             ->send();