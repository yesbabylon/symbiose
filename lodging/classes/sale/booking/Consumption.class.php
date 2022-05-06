<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class Consumption extends \sale\booking\Consumption {


    public static function getColumns() {
        return [

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the consumption relates.",
                'required'          => true,
                'ondelete'          => 'cascade',         // delete consumption when parent Center is deleted
                'readonly'          => true
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the comsumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent booking is deleted
                'readonly'          => true
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'description'       => 'The booking line the consumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent line is deleted
                'readonly'          => true
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'The booking line group the consumption relates to.',
                'ondelete'          => 'cascade',        // delete consumption when parent group is deleted
                'readonly'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => "The Product this Attribute belongs to.",
                'required'          => true,
                'readonly'          => true
            ]
        ];
    }

    /**
     * @param \equal\orm\ObjectManager $om
     */
    public static function _getExistingConsumptions($om, $centers_ids, $date_from, $date_to) {
        // read all consumptions and repairs (book, ooo, link, part)
        $consumptions_ids = $om->search(__CLASS__, [
                                ['date', '>=', $date_from],
                                ['date', '<=', $date_to],
                                ['center_id', 'in',  $centers_ids],
                                ['is_rental_unit', '=', true]
                            ], ['date' => 'asc']);

        $consumptions = $om->read(__CLASS__, $consumptions_ids, [
                                'id', 'date','schedule_from','schedule_to',
                                'rental_unit_id', 'booking_line_group_id'
                            ]);

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

                $rental_unit_id = $consumption['rental_unit_id'];
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

            // associative array for mapping processed consumptions: each consumption is present only once in the result set
            $processed_consumptions = [];
            foreach($consumptions as $consumption) {

                if(isset($processed_consumptions[$consumption['id']])) {
                    continue;
                }

                $date_index = substr(date('c', $consumption['date']), 0, 10);
                $rental_unit_id = $consumption['rental_unit_id'];
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
                }
                $result[$rental_unit_id][$date_index] = $consumption;
            }

        }
        return $result;
    }


    /**
     * @param \equal\orm\ObjectManager $om  Instance of Object Manager service.
     * @param int $center_id    Identifier of the center for which to perform the lookup.
     * @param int $product_id   Identifier of the product for which we are looking for rental units.
     * @param int $date_from    Timestamp of the first day of the lookup.
     * @param int $date_to      Timestamp of the last day of the lookup.
     */
    public static function _getAvailableRentalUnits($om, $center_id, $product_id, $date_from, $date_to) {

        // retrieve product and related product model
        $products = $om->read('lodging\sale\catalog\Product', $product_id, ['id', 'product_model_id']);

        if($products <= 0 || count($products) < 1) {
            return [];
        }
        $product = reset($products);

        $models = $om->read('lodging\sale\catalog\ProductModel', $product['product_model_id'], [
            'type','service_type','is_accomodation','schedule_offset','schedule_type','schedule_default_value',
            'rental_unit_assignement', 'rental_unit_category_id', 'rental_unit_id', 'capacity'
        ]);

        if($models <= 0 || count($models) < 1) {
            return [];
        }

        $product_model = reset($models);
        $product_type = $product_model['type'];
        $service_type = $product_model['service_type'];
        $schedule_default_value = $product_model['schedule_default_value'];
        $rental_unit_assignement = $product_model['rental_unit_assignement'];

        if($product_type != 'service' || $service_type != 'schedulable') {
            return [];
        }

        if($product_model['is_accomodation']) {
            // #todo - this actually means that the product relates to a rental unit (that might not be an accomodation)
            //  - we should check if the rental unit is an acoomodation
            // checkout is the day following the last night
            $date_to += 24*3600;
        }

        // retrieve chekin and checkout times related to product
        // #todo - what if we would agree to arrange the checkin/out times (not default values) to make the room available ?
        if(strpos($schedule_default_value, ':')) {
            $parts = explode('-', $schedule_default_value);
            list($hour_from, $minute_from) = explode(':', $parts[0]);
            list($hour_to, $minute_to) = [$hour_from+1, $minute_from];
            if(count($parts) > 1) {
                list($hour_to, $minute_to) = explode(':', $parts[1]);
            }
        }
        $schedule_from  = $hour_from * 3600 + $minute_from * 60;
        $schedule_to    = $hour_to * 3600 + $minute_to * 60;

        if($rental_unit_assignement == 'unit') {
            $rental_units_ids = [$product_model['rental_unit_id']];
        }
        else {
            $domain = [ ['center_id', '=', $center_id], ['is_accomodation', '=', true] ];
            if($rental_unit_assignement == 'category') {
                $rental_unit_category_id = $product_model['rental_unit_category_id'];

                if($rental_unit_category_id) {
                    $domain[] = ['rental_unit_category_id', '=', $rental_unit_category_id];
                }
            }
            // retrieve list of possible rental_units based on center_id
            $rental_units_ids = $om->search('lodging\realestate\RentalUnit', $domain, ['capacity' => 'desc']);

        }

        /*
            If there are consumptions in the range for some of the found rental units, remove those
        */
        $existing_consumptions_map = self::_getExistingConsumptions($om, [$center_id], $date_from, $date_to);

        $booked_rental_units_ids = [];

        foreach($existing_consumptions_map as $rental_unit_id => $dates) {
            foreach($dates as $date_index => $consumption) {

                if($consumption['date_from'] > $date_from && $consumption['date_to'] < $date_to) {
                    $booked_rental_units_ids[] = $rental_unit_id;
                    continue 2;
                }
                if($consumption['date_from'] <= $date_from) {
                    if($consumption['date_to'] > $date_from) {
                        $booked_rental_units_ids[] = $rental_unit_id;
                        continue 2;
                    }
                    else if($consumption['date_to'] == $date_from && $consumption['schedule_to'] > $schedule_from) {
                        $booked_rental_units_ids[] = $rental_unit_id;
                        continue 2;
                    }
                }
                if($consumption['date_to'] >= $date_to) {
                    if($consumption['date_from'] < $date_to) {
                        $booked_rental_units_ids[] = $rental_unit_id;
                        continue 2;
                    }
                    else if($consumption['date_from'] == $date_to && $consumption['schedule_from'] < $schedule_to) {
                        $booked_rental_units_ids[] = $rental_unit_id;
                        continue 2;
                    }
                }
            }
        }

        return $rental_units_ids;
        return array_diff($rental_units_ids, $booked_rental_units_ids);
    }
}