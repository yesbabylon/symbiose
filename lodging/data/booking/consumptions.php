<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Consumption;
use lodging\realestate\RentalUnit;

list($params, $providers) = announce([
    'description'   => "Retrieve the consumptions attached to rental units of specified centers and return an associative object mapping rental units with date indexes and related consumptions.",
    'params'        => [
        'centers_ids' =>  [
            'description'   => 'Identifiers of the centers for which the consumptions are requested.',
            'type'          => 'array',
            'required'      => true
        ],
        'date_from' => [
            'description'   => 'Start of time-range for the lookup.',
            'type'          => 'date',
            'required'      => true
        ],
        'date_to' => [
            'description'   => 'End of time-range for the lookup.',
            'type'          => 'date',
            'required'      => true
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
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$consumptions = Consumption::search([
                    ['date', '>=', $params['date_from']],
                    ['date', '<=', $params['date_to']],
                    ['center_id', 'in',  $params['centers_ids']],
                    ['is_rental_unit', '=', true]
                ],
                [
                    'sort' => ['date' => 'asc']
                ])
                ->read([
                    'date','schedule_from','schedule_to', 'is_rental_unit', 'qty', 'type',
                    'booking_id' => ['id', 'name', 'status', 'payment_status', 'customer_id' => ['id', 'name']],
                    'rental_unit_id' => ['id', 'name'],
                    'booking_line_id','booking_line_group_id'
                ])
                ->adapt('txt')
                ->get(true);

/*
    Result is a 2-level associative array, mapping consumptions by rental unit and date
*/
$result = [];


$sojourns_map = [];

if($consumptions > 0) {

    /*
        Join consecutive consumptions of a same booking_line_group for usingas same rental unit.
        All consumptions are enriched with additional fields `date_from`and `date_to`.
        Field schedule_from and schedule_to are adapted consequently.
    */

    // pass-1 : group consumptions by rental unit and booking line group
    foreach($consumptions as $index => $consumption) {
        if(!isset($consumption['rental_unit_id']) || empty($consumption['rental_unit_id'])) {
            // ignore consumptions not relating to a rental unit
            unset($consumptions[$index]);
            continue;
        }

        $rental_unit_id = $consumption['rental_unit_id']['id'];
        $booking_line_group_id = $consumption['booking_line_group_id'];

        if(!isset($sojourns_map[$rental_unit_id])) {
            $sojourns_map[$rental_unit_id] = [];
        }

        if(!isset($sojourns_map[$rental_unit_id][$booking_line_group_id])) {
            $sojourns_map[$rental_unit_id][$booking_line_group_id] = [];
        }

        $sojourns_map[$rental_unit_id][$booking_line_group_id][] = $consumption;
    }

    // pass-2 : generate map

    // associative array for mapping processed consumptoions: each consumption is present only once in the result set
    $processed_consumptions = [];
    foreach($consumptions as $consumption) {

        if(isset($processed_consumptions[$consumption['id']])) {
            continue;
        }

        $date_index = substr($consumption['date'], 0, 10);
        $rental_unit_id = $consumption['rental_unit_id']['id'];
        $booking_line_group_id = $consumption['booking_line_group_id'];

        if(!isset($result[$rental_unit_id])) {
            $result[$rental_unit_id] = [];
        }

        if(!isset($result[$rental_unit_id][$date_index])) {
            $result[$rental_unit_id][$date_index] = [];
        }

        $group_len = count($sojourns_map[$rental_unit_id][$booking_line_group_id]);
        if(isset($sojourns_map[$rental_unit_id][$booking_line_group_id]) && $group_len > 0) {

            foreach($sojourns_map[$rental_unit_id][$booking_line_group_id] as $group_consumption) {
                $processed_consumptions[$group_consumption['id']] = true;
            }
            if($group_len == 1) {
                $consumption['date_from'] = $consumption['date'];
                $consumption['date_to'] = $consumption['date'];
                $result[$rental_unit_id][$date_index] = $consumption;
            }
            else {
                $first = $sojourns_map[$rental_unit_id][$booking_line_group_id][0];
                $last = $sojourns_map[$rental_unit_id][$booking_line_group_id][$group_len-1];

                $consumption['date_from'] = $first['date'];
                $consumption['date_to'] = $last['date'];
                $consumption['schedule_to'] = $last['schedule_to'];
            }
        }
        else {
            $consumption['date_from'] = $consumption['date'];
            $consumption['date_to'] = $consumption['date'];
            $consumption['schedule_to'] = $last['schedule_to'];
        }
        $consumption['is_full'] = true;
        $result[$rental_unit_id][$date_index] = $consumption;
    }

    // pass-3 : handle parents and children units

    /*
        A rental unit is always booked entirely (ex. Having a dorm room with several beds that can be booked separately, the rental unit for involved bookings are the beds [not the room] )
        - If a booked rental unit has children units, theses are marked as occupied.
        - If a booked rental unit has a parent,
            if the parent allows partial rent, then the parent unit is marked as partially occupied, unless all of its units are occupied
            otherwise the parent is marked as fully occupied
        #memo - Special rental units that can be booked either fully or partially are modelised as 2 distincts rental units.
    */



    $rental_units_ids = [];

    // read a first level
    $units = $orm->read('lodging\realestate\RentalUnit', array_keys($result), ['parent_id', 'children_ids']);

    if($units > 0) {
        foreach($units as $uid => $unit) {
            $rental_units_ids[] = $uid;

            if($unit['parent_id'] > 0) {
                $rental_units_ids[] = $unit['parent_id'];
            }
            if(count($unit['children_ids'])) {
                $rental_units_ids = array_merge($rental_units_ids, $unit['children_ids']);
            }

        }
    }

    // read a second level
    $units = $orm->read('lodging\realestate\RentalUnit', $rental_units_ids, ['parent_id', 'children_ids', 'can_partial_rent']);

    foreach($result as $rental_unit_id => $descriptor) {

        $unit_id = $rental_unit_id;
        $parents_ids = [];

        while( isset($units[$unit_id]) ) {
            $parent_id = $units[$unit_id]['parent_id'];
            if($parent_id > 0) {
                $parents_ids[] = $parent_id;
            }
            $unit_id = $parent_id;
        }

        $unit_id = $rental_unit_id;
        $children_ids = [];
        $children_stack = $units[$unit_id]['children_ids'];
        while(count($children_stack)) {
            $unit_id = array_pop($children_stack);
            $children_ids[] = $unit_id;
            if(isset($units[$unit_id]) && $units[$unit_id]['children_ids']) {
                foreach($units[$unit_id]['children_ids'] as $child_id) {
                    $children_stack[] = $child_id;
                }
            }
        }

        foreach($descriptor as $date_index => $consumption) {
            foreach($children_ids as $child_id) {
                if(!isset($result[$child_id]) || !isset($result[$child_id][$date_index])) {
                    // child units depend on their parent unit, if full then the child is full as well
                    if(isset($units[$child_id]['parent_id'])) {
                        $parent_id = $units[$child_id]['parent_id'];
                        if(isset($result[$parent_id][$date_index])) {
                            if(!isset($units[$parent_id]['can_partial_rent']) || !$units[$parent_id]['can_partial_rent']) {
                                $result[$child_id][$date_index] = array_merge($consumption, ['is_full' => true]);
                            }
                        }
                    }
                }
            }
            foreach($parents_ids as $parent_id) {
                if(!isset($result[$parent_id]) || !isset($result[$parent_id][$date_index])) {
                    $is_full = false;
                    // add info about full or partial occupation
                    if(isset($units[$parent_id]['can_partial_rent']) && !$units[$parent_id]['can_partial_rent']) {
                        $is_full = true;
                    }
                    else {
                        $count = 0;
                        foreach($units[$parent_id]['children_ids'] as $child_id) {
                            if(isset($result[$child_id][$date_index])) {
                                ++$count;
                            }
                        }
                        if($count == count($units[$parent_id]['children_ids'])) {
                            $is_full = true;
                        }
                    }
                    $result[$parent_id][$date_index] = array_merge($consumption, ['is_full' => $is_full]);
                }
            }
        }
    }

}


$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();