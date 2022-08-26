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
                'readonly'          => true,
                'onupdate'          => 'onupdateBookingId'
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
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => "The rental unit the consumption is assigned to.",
                'readonly'          => true,
                'onupdate'          => 'sale\booking\Consumption::onupdateRentalUnitId'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The customer whom the consumption relates to (computed).",
            ],

            'time_slot_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\TimeSlot',
                'description'       => 'Indicator of the moment of the day when the consumption occurs (from schedule).',
            ],

            'schedule_from' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the events starts.',
                'default'           => 0,
                'onupdate'          => 'onupdateScheduleFrom'
            ],

            'schedule_to' => [
                'type'              => 'time',
                'description'       => 'Moment of the day at which the event stops, if applicable.',
                'default'           => 24 * 3600,
                'onupdate'          => 'onupdateScheduleTo'
            ],

        ];
    }

    /**
     * Hook invoked before object update for performing object-specific additional operations.
     * Current values of the object can still be read for comparing with new values.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  array                      $oids       List of objects identifiers.
     * @param  array                      $values     Associative array holding the new values that have been assigned.
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return void
     */
    public static function onupdate($om, $oids, $values, $lang) {
        if(isset($values['qty'])) {
            $consumptions = $om->read(__CLASS__, $oids, ['qty', 'booking_id', 'booking_line_id.product_id', 'booking_line_id.unit_price', 'booking_line_id.vat_rate', 'booking_line_id.qty_accounting_method'], $lang);
            foreach($consumptions as $cid => $consumption) {
                if($consumption['qty'] < $values['qty'] && in_array($consumption['booking_line_id.qty_accounting_method'], ['person', 'unit'])) {
                    $diff = $values['qty'] - $consumption['qty'];
                    // in is_extra group, add a new line with same product as targeted booking_line
                    $groups_ids = $om->search('lodging\sale\booking\BookingLineGroup', [['booking_id', '=', $consumption['booking_id']], ['is_extra', '=', true]]);
                    if($groups_ids > 0 && count($groups_ids)) {
                        $group_id = reset(($groups_ids));
                    }
                    else {
                        // create extra group
                        $group_id = $om->create('lodging\sale\booking\BookingLineGroup', ['name' => 'Suppléments', 'booking_id' => $consumption['booking_id'], 'is_extra' => true]);
                    }
                    // create a new bookingLine
                    $line_id = $om->create('lodging\sale\booking\BookingLine', ['booking_id' => $consumption['booking_id'], 'booking_line_group_id' => $group_id, 'product_id' => $consumption['booking_line_id.product_id']], $lang);
                    // #memo - at creation booking_line qty is always set accordingly to its parent group nb_pers
                    $om->update('lodging\sale\booking\BookingLine', $line_id, ['qty' => $diff, 'unit_price' => $consumption['booking_line_id.unit_price'], 'vat_rate' => $consumption['booking_line_id.vat_rate']], $lang);
                }
            }
        }
        parent::onupdate($om, $oids, $values, $lang);
    }

    public static function onupdateBookingId($om, $oids, $values, $lang) {
        $consumptions = $om->read(__CLASS__, $oids, ['booking_id.customer_id'], $lang);
        if($consumptions) {
            foreach($consumptions as $cid => $consumption) {
                $om->update(__CLASS__, $cid, [ 'customer_id' => $consumption['booking_id.customer_id'] ], $lang);
            }
        }
    }

    /**
     * Hook invoked after updates on field `schedule_from`.
     * Adapt time_slot_id according to new moment.
     * Update siblings consumptions (same day same line) relating to rental units to use the same value for schedule_from.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  int[]                      $oids       List of objects identifiers in the collection.
     * @param  array                      $values     Associative array holding the values newly assigned to the new instance (not all fields might be set).
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return void
     */
    public static function onupdateScheduleFrom($om, $oids, $values, $lang) {
        // booking_id is only assigned upon creation, so hook is called because of an update (not a creation)
        if(!isset($values['booking_id'])) {
            $consumptions = $om->read(self::getType(), $oids, ['is_rental_unit', 'date', 'schedule_from', 'booking_line_id'], $lang);
            if($consumptions > 0) {
                foreach($consumptions as $oid => $consumption) {
                    if($consumption['is_rental_unit']) {
                        $siblings_ids = $om->search(self::getType(), [['id', '<>', $oid], ['is_rental_unit', '=', true], ['booking_line_id', '=', $consumption['booking_line_id']], ['date', '=', $consumption['date']] ]);
                        if($siblings_ids > 0 && count($siblings_ids)) {
                            $om->update(self::getType(), $siblings_ids, ['schedule_from' => $consumption['schedule_from']]);
                        }
                    }
                }
            }
        }
        $om->callonce(__CLASS__, '_updateTimeSlotId', $oids, $values, $lang);
    }

    /**
     * Hook invoked after updates on field `schedule_to`.
     * Adapt time_slot_id according to new moment.
     * Update siblings consumptions (same day same line) relating to rental units to use the same value for schedule_to.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  int[]                      $oids       List of objects identifiers in the collection.
     * @param  array                      $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return void
     */
    public static function onupdateScheduleTo($om, $oids, $values, $lang) {
        // booking_id is only assigned upon creation, so hook is called because of an update (not a creation)
        if(!isset($values['booking_id'])) {
            $consumptions = $om->read(self::getType(), $oids, ['is_rental_unit', 'date', 'schedule_to', 'booking_line_id'], $lang);
            if($consumptions > 0) {
                foreach($consumptions as $oid => $consumption) {
                    if($consumption['is_rental_unit']) {
                        $siblings_ids = $om->search(self::getType(), [['id', '<>', $oid], ['is_rental_unit', '=', true], ['booking_line_id', '=', $consumption['booking_line_id']], ['date', '=', $consumption['date']] ]);
                        if($siblings_ids > 0 && count($siblings_ids)) {
                            $om->update(self::getType(), $siblings_ids, ['schedule_to' => $consumption['schedule_to']]);
                        }
                    }
                }
            }
        }
        $om->callonce(__CLASS__, '_updateTimeSlotId', $oids, $values, $lang);
    }

    public static function _updateTimeSlotId($om, $oids, $values, $lang) {
        $consumptions = $om->read(__CLASS__, $oids, ['schedule_from', 'schedule_to']);
        if($consumptions > 0) {
            $moments_ids = $om->search('lodging\sale\booking\TimeSlot', [], ['order' => 'asc']);
            $moments = $om->read('lodging\sale\booking\TimeSlot', $moments_ids, ['schedule_from', 'schedule_to']);
            foreach($consumptions as $cid => $consumption) {
                // retrieve timeslot according to schedule_from
                foreach($moments as $mid => $moment) {
                    if($consumption['schedule_from'] >= $moment['schedule_from'] && $consumption['schedule_to'] < $moment['schedule_to']) {
                        $om->update(__CLASS__, $cid, ['time_slot_id' => $mid]);
                    }
                }
            }
        }
    }

    /**
     *
     * #memo - used in controllers
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

                // retrieve UTC timestamp
                $moment = $consumption['date'] - date('Z', $consumption['date']) + $consumption['schedule_from'];
                $date_index = substr(date('c', $moment), 0, 10);

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
     *
     * #memo - This method is used in controllers
     *
     * @param \equal\orm\ObjectManager $om  Instance of Object Manager service.
     * @param int $center_id    Identifier of the center for which to perform the lookup.
     * @param int $product_id   Identifier of the product for which we are looking for rental units.
     * @param int $date_from    Timestamp of the first day of the lookup.
     * @param int $date_to      Timestamp of the last day of the lookup.
     */
    public static function _getAvailableRentalUnits($om, $center_id, $product_id, $date_from, $date_to) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\Consumption:_getAvailableRentalUnits", QN_REPORT_DEBUG);

        // retrieve product and related product model
        $products = $om->read('lodging\sale\catalog\Product', $product_id, ['id', 'product_model_id']);

        if($products <= 0 || count($products) < 1) {
            return [];
        }
        $product = reset($products);

        $models = $om->read('lodging\sale\catalog\ProductModel', $product['product_model_id'], [
            'type','service_type','is_accomodation','schedule_offset','schedule_type',
            'rental_unit_assignement', 'rental_unit_category_id', 'rental_unit_id', 'capacity'
        ]);

        if($models <= 0 || count($models) < 1) {
            return [];
        }

        $product_model = reset($models);
        $product_type = $product_model['type'];
        $service_type = $product_model['service_type'];
        $rental_unit_assignement = $product_model['rental_unit_assignement'];

        if($product_type != 'service' || $service_type != 'schedulable') {
            return [];
        }

        if($rental_unit_assignement == 'unit') {
            $rental_units_ids = [$product_model['rental_unit_id']];
        }
        else {
            $domain = [ ['center_id', '=', $center_id] ];

            if($product_model['is_accomodation']) {
                $domain[] = ['is_accomodation', '=', true];
            }

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

                $consumption_from = $consumption['date'] + $consumption['schedule_from'];
                $consumption_to = $consumption['date'] + $consumption['schedule_to'];
                // we don't allow instant transition (checkin of a booking == checkout of previous booking)
                if( ($consumption_from >= $date_from && $consumption_from <= $date_to) || ($consumption_to >= $date_from && $consumption_to <= $date_to) ) {
                    $booked_rental_units_ids[] = $rental_unit_id;
                    continue 2;
                }
            }
        }

        return array_diff($rental_units_ids, $booked_rental_units_ids);
    }
}