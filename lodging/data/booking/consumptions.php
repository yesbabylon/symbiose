<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Consumption;

list($params, $providers) = announce([
    'description'   => "Retrieve the consumptions attached to rental units of specified centers for a given time range.",
    'params'        => [
        'centers_ids' =>  [
            'description'   => 'Identifiers of the centers for which the consumptions are requested.',
            'type'          => 'array',
            'required'      => true
        ],
        'date_from' => [
            'description'   => 'Start of the time-range for the lookup.',
            'type'          => 'date',
            'required'      => true
        ],
        'date_to' => [
            'description'   => 'End of the time-range for the lookup.',
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
                    ['center_id', 'in',  $params['centers_ids']]
                ], 
                [ 
                    'sort' => ['date' => 'asc'] 
                ])
                ->read([
                    'date','schedule_from','schedule_to', 'is_accomodation',
                    'booking_id' => ['id', 'name', 'status', 'payment_status', 'customer_id' => ['id', 'name']],
                    'rental_unit_id' => ['id', 'children_ids'],
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
        Join consecutive consumptions of a same booking_line_group for as same rental unit.
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

        $result[$rental_unit_id][$date_index] = $consumption;
    }

}


$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();