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
    'description'   => "Checks the consistency of rental units assignments for a given booking (number of persons).",
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
            'is_sojourn',
            'is_event',
            'nb_pers',
            'nb_nights',
            'booking_lines_ids' => [
                'is_rental_unit',
                'qty_vars',
                'product_model_id'
            ],
            'sojourn_product_models_ids' => [
                'product_model_id',
                'rental_unit_assignments_ids'
            ]
        ]
    ])
    ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

$booking_line_groups = $booking['booking_lines_groups_ids'];
$mismatch = false;

if($booking_line_groups) {
    foreach($booking_line_groups as $gid => $group) {

        $nb_pers = $group['nb_pers'];
        $assigned_count = 0;

        // keep only lines relating to rental units
        $lines = array_filter($group['booking_lines_ids'], function($a) { return $a['is_rental_unit']; });

        // read all related product models at once
        $product_models_ids = array_map(function($a) {return $a['product_model_id'];}, $lines);
        $product_models = ProductModel::ids($product_models_ids)
            ->read([
                'is_accomodation',
                'qty_accounting_method',
                'rental_unit_assignement',
                'capacity'
            ])
            ->get();

        $spms = [];

        // pass-1 : withdraw persons assigned to units accounted by 'accomodation' from nb_pers

        foreach($lines as $lid => $line) {
            $product_model_id = $line['product_model_id'];
            if($product_models[$product_model_id]['qty_accounting_method'] == 'accomodation') {
                // this doesn't seem to be correct: only real assignment should be considered
                // $nb_pers -= $product_models[$product_model_id]['capacity'];
            }
            if(!isset($spms[$product_model_id])) {
                $spms[$product_model_id] = [];
            }
            $spms[$product_model_id][] = $line;
        }

        // pass-2 : find max pers by product_model
        $spms_max = [];
        $factor = max(1, $group['nb_nights']);

        foreach($spms as $product_model_id => $lines) {
            $days_nb_pers = array_pad([], $factor, $nb_pers);
            foreach($lines as $line) {
                $qty_vars = json_decode($line['qty_vars']);
                if($qty_vars) {
                    foreach($qty_vars as $i => $variation) {
                        $days_nb_pers[$i] += $variation;
                    }
                }
            }
            $spms_max[$product_model_id] = max($days_nb_pers);
        }

        foreach($group['sojourn_product_models_ids'] as $oid => $spm) {
            $product_model_id = $spm['product_model_id'];
            if($product_models[$product_model_id]['qty_accounting_method'] == 'accomodation') {
                // this doesn't seem to be correct: only real assignment should be considered
                // continue;
            }
            // there must be at least one assignment (one rental unit)
            if(!count($spm['rental_unit_assignments_ids'])) {
                $mismatch = true;
                break;
            }
            // we dont set quantity restrictions on non-accomodation rental units (meeting rooms, furniture, ...)
            if(!$product_models[$product_model_id]['is_accomodation']) {
                continue;
            }
            $assignments = SojournProductModelRentalUnitAssignement::ids($spm['rental_unit_assignments_ids'])
                ->read([
                    'is_accomodation', 'qty', 'rental_unit_id' => ['capacity']
                ])
                ->get();
            // check the total assigned persons against the group nb_pers
            $total_capacity = array_reduce($assignments, function($c, $a) {return $c + $a['rental_unit_id']['capacity'];}, 0);
            if($spms_max[$product_model_id] > $total_capacity) {
                trigger_error("QN_DEBUG_ORM::max {$spms_max[$product_model_id]} for $product_model_id is greater than total capacity $total_capacity", QN_REPORT_DEBUG);
                $mismatch = true;
                break 2;
            }

        }

    }
}




/*
    This controller is a check: an empty response means that no alert was raised
*/

$result = [];
$httpResponse = $context->httpResponse()->status(200);


if($mismatch) {
    $result[] = $params['id'];
    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.rental_units_assignment', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-units-assignments', ['id' => $params['id']],[],null,$booking['center_office_id']);
    $httpResponse->status(qn_error_http(QN_ERROR_NOT_ALLOWED));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.rental_units_assignment', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();