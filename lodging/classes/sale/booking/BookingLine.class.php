<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class BookingLine extends \sale\booking\BookingLine {

    public static function getName() {
        return "Booking line";
    }

    public static function getDescription() {
        return "Booking lines describe the products and quantities that are part of a booking.";
    }

    public static function getColumns() {
        return [

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product items for the line.',
                'onupdate'          => 'onupdateQty',
                'default'           => 1.0
            ],

            'is_rental_unit' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to a rental unit (from product_model).',
                'function'          => 'calcIsRentalUnit',
                'store'             => true
            ],

            'is_accomodation' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to an accomodation(from product_model).',
                'function'          => 'calcIsAccomodation',
                'store'             => true
            ],

            'is_meal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to a meal (from product_model).',
                'function'          => 'calcIsMeal',
                'store'             => true
            ],

            'qty_accounting_method' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Quantity accounting method (from product_model).',
                'function'          => 'calcQtyAccountingMethod',
                'store'             => true
            ],

            'qty_vars' => [
                'type'              => 'text',
                'description'       => 'JSON array holding qty variation deltas (for \'by person\' products), if any.',
                'onupdate'          => 'onupdateQtyVars'
            ],

            'is_autosale' => [
                'type'              => 'boolean',
                'description'       => 'Does the line relate to an autosale product?',
                'default'           => false
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BookingLineGroup::getType(),
                'description'       => 'Group the line relates to (in turn, groups relate to their booking).',
                'required'          => true,             // must be set at creation
                'onupdate'          => 'onupdateBookingLineGroupId',
                'ondelete'          => 'cascade'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'onupdate'          => 'onupdateProductId'
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\ProductModel',
                'description'       => 'The product model the line relates to (from product).'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Consumption',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Consumptions related to the booking line.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => BookingPriceAdapter::getType(),
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Price adapters holding the manual discounts applied on the line.',
                'onupdate'          => 'sale\booking\BookingLine::onupdatePriceAdaptersIds'
            ]

        ];
    }

    public static function calcIsAccomodation($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:calcIsAccomodation", QN_REPORT_DEBUG);

        $result = [];
        $lines = $om->read(__CLASS__, $oids, [
            'product_id.product_model_id.is_accomodation'
        ]);
        if($lines > 0 && count($lines)) {
            foreach($lines as $oid => $odata) {
                $result[$oid] = $odata['product_id.product_model_id.is_accomodation'];
            }
        }
        return $result;
    }

    public static function calcIsRentalUnit($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:calcIsRentalUnit", QN_REPORT_DEBUG);

        $result = [];
        $lines = $om->read(__CLASS__, $oids, [
            'product_id.product_model_id.is_rental_unit'
        ]);
        if($lines > 0 && count($lines)) {
            foreach($lines as $oid => $odata) {
                $result[$oid] = $odata['product_id.product_model_id.is_rental_unit'];
            }
        }
        return $result;
    }

    public static function calcIsMeal($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:calcIsMeal", QN_REPORT_DEBUG);

        $result = [];
        $lines = $om->read(__CLASS__, $oids, [
            'product_id.product_model_id.is_meal'
        ]);
        if($lines > 0 && count($lines)) {
            foreach($lines as $oid => $odata) {
                $result[$oid] = $odata['product_id.product_model_id.is_meal'];
            }
        }
        return $result;
    }

    public static function calcQtyAccountingMethod($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:calcQtyAccountingMethod", QN_REPORT_DEBUG);

        $result = [];
        $lines = $om->read(__CLASS__, $oids, [
            'product_id.product_model_id.qty_accounting_method'
        ]);
        if($lines > 0 && count($lines)) {
            foreach($lines as $oid => $odata) {
                $result[$oid] = $odata['product_id.product_model_id.qty_accounting_method'];
            }
        }
        return $result;
    }

    /**
     *
     * New group assignement (should be called upon creation only)
     *
     */
    public static function onupdateBookingLineGroupId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateBookingLineGroupId", QN_REPORT_DEBUG);
    }

    /**
     * Update the price_id according to booking line settings.
     *
     * This method is called at booking line creation if product_id is amongst the fields.
     */
    public static function onupdateProductId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateProductId", QN_REPORT_DEBUG);

        /*
            update product model according to newly set product
        */
        $lines = $om->read(self::getType(), $oids, ['product_id.product_model_id', 'booking_line_group_id', 'booking_line_group_id.has_locked_rental_units'], $lang);
        foreach($lines as $lid => $line) {
            $om->update(self::getType(), $lid, ['product_model_id' => $line['product_id.product_model_id']]);
        }

        /*
            reset computed fields related to product model
        */
        $om->update(self::getType(), $oids, ['name' => null, 'qty_accounting_method' => null, 'is_rental_unit' => null, 'is_accomodation' => null, 'is_meal' => null]);

        /*
            resolve price_id according to new product_id
        */
        $om->callonce(self::getType(), '_updatePriceId', $oids, [], $lang);

        /*
            check booking type and chekin/out times dependencies, and auto-assign qty if required
        */

        $lines = $om->read(self::getType(), $oids, [
                'product_id.product_model_id.booking_type_id',
                'product_id.product_model_id',
                'product_id.has_age_range',
                'product_id.age_range_id',
                'booking_id',
                'booking_line_group_id',
                'booking_line_group_id.has_locked_rental_units',
                'booking_line_group_id.nb_pers',
                'booking_line_group_id.nb_nights',
                'booking_line_group_id.nb_pers',
                'booking_line_group_id.age_range_assignments_ids',
                'qty',
                'has_own_qty',
                'is_rental_unit',
                'is_accomodation',
                'is_meal',
                'qty_accounting_method'
            ], $lang);

        foreach($lines as $lid => $line) {
            // if model of chosen product has a non-generic booking type, update the booking of the line accordingly
            if(isset($line['product_id.product_model_id.booking_type_id']) && $line['product_id.product_model_id.booking_type_id'] != 1) {
                $om->update('lodging\sale\booking\Booking', $line['booking_id'], ['type_id' => $line['product_id.product_model_id.booking_type_id']]);
            }

            // if line is a rental unit, use its related product info to update parent group schedule, if possible
            if($line['is_rental_unit']) {
                $models = $om->read(\lodging\sale\catalog\ProductModel::getType(), $line['product_id.product_model_id'], ['type', 'service_type', 'schedule_type', 'schedule_default_value'], $lang);
                if($models > 0 && count($models)) {
                    $model = reset($models);
                    if($model['type'] == 'service' && $model['service_type'] == 'schedulable' && $model['schedule_type'] == 'timerange') {
                        // retrieve relative timestamps
                        $schedule = $model['schedule_default_value'];
                        if(strlen($schedule)) {
                            $times = explode('-', $schedule);
                            $parts = explode(':', $times[0]);
                            $schedule_from = $parts[0]*3600 + $parts[1]*60;
                            $parts = explode(':', $times[1]);
                            $schedule_to = $parts[0]*3600 + $parts[1]*60;
                            // update the parent group schedule
                            $om->update(BookingLineGroup::getType(), $line['booking_line_group_id'], ['time_from' => $schedule_from, 'time_to' => $schedule_to], $lang);
                        }
                    }
                }
            }
        }
        // if qty is not amongst the updated fields, retrieve the quantity to assign to each line
        if(!isset($values['qty'])) {
            foreach($lines as $lid => $line) {
                $qty = $line['qty'];
                if(!$line['has_own_qty']) {
                    if($line['qty_accounting_method'] == 'accomodation') {
                        // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the sojourn
                        // which should have been stored in the nb_nights field
                        $qty = $line['booking_line_group_id.nb_nights'];
                    }
                    else if($line['qty_accounting_method'] == 'person') {
                        $age_assignments = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $line['booking_line_group_id.age_range_assignments_ids'], ['age_range_id', 'qty']);
                        $nb_pers = $line['booking_line_group_id.nb_pers'];
                        if($line['product_id.has_age_range']) {
                            foreach($age_assignments as $aid => $assignment) {
                                if($assignment['age_range_id'] == $line['product_id.age_range_id']) {
                                    $nb_pers = $assignment['qty'];
                                }
                            }
                        }
                        // lines having a product 'by person' have a qty assigned to the 'duration' x 'nb_pers' of the sojourn
                        // which should have been stored in the nb_pers field
                        if($line['is_meal'] || $line['is_accomodation']) {
                            $qty = $nb_pers * max(1, $line['booking_line_group_id.nb_nights']);
                        }
                        else {
                            $qty = $nb_pers;
                        }
                    }
                }

                if($qty != $line['qty'] || $line['is_rental_unit']) {
                    $om->update(self::getType(), $lid, ['qty' => $qty]);
                }
            }
        }

        /*
            update parent groups rental unit assignments
        */

        // group lines by booking_line_group
        $sojourns = [];
        foreach($lines as $lid => $line) {
            // do not update rental unit assignements for lines whose group is marked as locked rental units
            if($line['booking_line_group_id.has_locked_rental_units']) {
                continue;
            }

            $gid = $line['booking_line_group_id'];

            if(!isset($sojourns[$gid])) {
                $sojourns[$gid] = [];
            }
            $sojourns[$gid][] = $lid;
        }
        foreach($sojourns as $gid => $lines_ids) {
            // retrieve all impacted product_models
            $olines = $om->read(self::getType(), $lines_ids, ['product_id.product_model_id'], $lang);
            $product_models_ids = array_map(function($a) { return $a['product_id.product_model_id'];}, $olines);
            // remove all assignments relating to found product_model
            $spm_ids = $om->search(SojournProductModel::getType(), ['product_model_id', 'in', $product_models_ids]);
            $om->remove(SojournProductModel::getType(), $spm_ids, true);
            // retrieve all lines from parent group that need to be reassigned
            // #memo - we need to handle these all at a time to avoid assigning a same rental unit twice
            $lines_ids = $om->search(self::getType(), [['booking_line_group_id', '=', $gid], ['product_model_id', 'in', $product_models_ids]], $lang);
            // recreate rental unit assignments
            $om->callonce(BookingLineGroup::getType(), 'createRentalUnitsAssignmentsFromLines', $gid, $lines_ids, $lang);
        }


        /*
            update parent groups price adapters
        */

        // group lines by booking_line_group
        $sojourns = [];
        foreach($lines as $lid => $line) {
            $gid = $line['booking_line_group_id'];
            if(!isset($sojourns[$gid])) {
                $sojourns[$gid] = [];
            }
            $sojourns[$gid][] = $lid;
        }
        foreach($sojourns as $gid => $lines_ids) {
            $om->callonce(BookingLineGroup::getType(), 'updatePriceAdaptersFromLines', $gid, $lines_ids, $lang);
        }

        /*
            reset computed fields related to price
        */
        $om->callonce(self::getType(), '_resetPrices', $oids, [], $lang);

    }

    public static function onupdateQtyVars($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateQtyVars", QN_REPORT_DEBUG);

        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, [], $lang);

        $lines = $om->read(self::getType(), $oids, [
            'booking_line_group_id.nb_pers',
            'qty_vars',
            'booking_line_group_id.age_range_assignments_ids',
            'product_id.has_age_range',
            'product_id.age_range_id'
        ]);

        if($lines > 0) {
            // set quantities according to qty_vars arrays
            foreach($lines as $lid => $line) {
                $nb_pers = $line['booking_line_group_id.nb_pers'];
                if($line['product_id.has_age_range']) {
                    $age_range_assignements = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $line['booking_line_group_id.age_range_assignments_ids'], ['age_range_id', 'qty']);
                    foreach($age_range_assignements as $aid => $assignment) {
                        if($assignment['age_range_id'] == $line['product_id.age_range_id']) {
                            $nb_pers = $assignment['qty'];
                            break;
                        }
                    }
                }
                // qty_vars should be a JSON array holding a series of deltas
                $qty_vars = json_decode($line['qty_vars']);
                if($qty_vars) {
                    $qty = 0;
                    foreach($qty_vars as $variation) {
                        $qty += $nb_pers + $variation;
                    }
                    $om->update(self::getType(), $lid, ['qty' => $qty]);
                }
                else {
                    $om->callonce(self::getType(), '_updateQty', $oids, [], $lang);
                }
            }
        }
    }


    /**
     * Update the quantity of products.
     *
     * This handler is called at booking line creation and all subsequent qty updates.
     * It is in charge of updating the rental units assignments related to the line.
     */
    public static function onupdateQty($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateQty", QN_REPORT_DEBUG);

        // Reset computed fields related to price (because they depend on qty)
        $om->callonce(self::getType(), '_resetPrices', $oids, [], $lang);
    }


    /**
     * Check wether an object can be created, and optionally perform additional operations.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  array                      $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        $bookings = $om->read(Booking::getType(), $values['booking_id'], ['status'], $lang);
        $groups = $om->read(BookingLineGroup::getType(), $values['booking_line_group_id'], ['is_extra'], $lang);

        if($bookings > 0 && $groups > 0) {
            $booking = reset($bookings);
            $group = reset($groups);

            if( in_array($booking['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                || ($booking['status'] != 'quote' && !$group['is_extra']) ) {
                return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
            }
        }

        return parent::cancreate($om, $values, $lang);
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang=DEFAULT_LANG) {

        // handle exceptions for fields that can always be updated
        $allowed = ['is_contractual', 'is_invoiced'];
        $count_non_allowed = 0;

        foreach($values as $field => $value) {
            if(!in_array($field, $allowed)) {
                ++$count_non_allowed;
            }
        }

        if($count_non_allowed > 0) {
            $lines = $om->read(self::getType(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra'], $lang);
            if($lines > 0) {
                foreach($lines as $line) {
                    if($line['booking_line_group_id.is_extra']) {
                        if(!in_array($line['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                            return ['booking_id' => ['non_editable' => 'Extra Services can only be updated after confirmation and before invoicing.']];
                        }
                    }
                    else {
                        if($line['booking_id.status'] != 'quote') {
                            return ['booking_id' => ['non_editable' => 'Services cannot be updated for non-quote bookings.']];
                        }
                    }
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether an object can be deleted, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @return boolean  Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $oids) {
        $lines = $om->read(self::getType(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra']);

        if($lines > 0) {
            foreach($lines as $line) {
                if($line['booking_line_group_id.is_extra']) {
                    if(!in_array($line['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                        return ['booking_id' => ['non_editable' => 'Extra Services can only be updated after confirmation and before invoicing.']];
                    }
                }
                else {
                    if($line['booking_id.status'] != 'quote') {
                        return ['booking_id' => ['non_editable' => 'Services cannot be updated for non-quote bookings.']];
                    }
                }
            }
        }

        return parent::candelete($om, $oids);
    }

    /**
     * Update the quantity according to parent group (pack_id, nb_pers, nb_nights) and variation array.
     * This method is triggered on fields update from BookingLineGroup or onupdateQtyVars from BookingLine.
     *
     */
    public static function _updateQty($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updateQty", QN_REPORT_DEBUG);

        $lines = $om->read(self::getType(), $oids, [
                'has_own_qty',
                'qty_vars',
                'booking_line_group_id.has_locked_rental_units',
                'booking_line_group_id.nb_pers',
                'booking_line_group_id.nb_nights',
                'booking_line_group_id.age_range_assignments_ids',
                'product_id.has_age_range',
                'product_id.age_range_id',
                'product_id.product_model_id.qty_accounting_method',
                'product_id.product_model_id.is_rental_unit',
                'product_id.product_model_id.is_meal',
                'product_id.product_model_id.has_duration',
                'product_id.product_model_id.duration'
            ],
            $lang);

        if($lines > 0) {

            // second-pass: update lines quantities
            foreach($lines as $lid => $line) {
                if($line['has_own_qty']) {
                    // own quantity has been assigned in onupdateProductId
                }
                else {
                    if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                        $om->update(self::getType(), $lid, ['qty' => $line['booking_line_group_id.nb_nights']]);
                    }
                    else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                        $qty_vars = json_decode($line['qty_vars']);
                        if(!$qty_vars) {
                            $factor = 1;
                            if($line['product_id.product_model_id.has_duration']) {
                                $factor = $line['product_id.product_model_id.duration'];
                            }
                            else if($line['product_id.product_model_id.is_rental_unit'] || $line['product_id.product_model_id.is_meal'] ) {
                                $factor = max(1, $line['booking_line_group_id.nb_nights']);
                            }
                            // nb_pers is either nb_pers or age_range.qty
                            $nb_pers = $line['booking_line_group_id.nb_pers'];
                            if($line['product_id.has_age_range']) {
                                $age_range_assignements = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $line['booking_line_group_id.age_range_assignments_ids'], ['age_range_id', 'qty']);
                                foreach($age_range_assignements as $aid => $assignment) {
                                    if($assignment['age_range_id'] == $line['product_id.age_range_id']) {
                                        $nb_pers = $assignment['qty'];
                                        break;
                                    }
                                }
                            }
                            $qty = $nb_pers * $factor;
                            $qty_vars = array_fill(0, $factor, 0);
                            // #memo - triggers onupdateQty and onupdateQtyVar
                            $om->update(self::getType(), $lid, ['qty' => $qty, 'qty_vars' => json_encode($qty_vars)]);
                        }
                        // qty_vars is set and valid
                        else {
                            $factor = $line['booking_line_group_id.nb_nights'];
                            if($line['product_id.product_model_id.has_duration']) {
                                $factor = $line['product_id.product_model_id.duration'];
                            }
                            $diff = $factor - count($qty_vars);
                            if($diff > 0) {
                                $qty_vars = array_pad($qty_vars, $factor, 0);
                            }
                            else if($diff < 0) {
                                $qty_vars = array_slice($qty_vars, 0, $factor);
                            }
                            // #memo - will trigger onupdateQtyVar which will update qty
                            $om->update(self::getType(), $lid, ['qty_vars' => json_encode($qty_vars)]);
                        }
                    }
                }
            }
        }
    }


    /**
     * Try to assign the price_id according to the current product_id.
     * Resolve the price from the first applicable price list, based on booking_line_group settings and booking center.
     * If found price list is pending, mark the booking as TBC.
     *
     * _updatePriceId is also called upon booking_id.center_id and booking_line_group_id.date_from changes.
     *
     * @param \equal\orm\ObjectManager $om
     */
    public static function _updatePriceId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updatePriceId", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, [
            'booking_line_group_id.date_from',
            'product_id',
            'booking_id',
            'booking_id.center_id.price_list_category_id'
        ]);

        foreach($lines as $line_id => $line) {
            /*
                Find the Price List that matches the criteria from the booking (shortest duration first)
            */
            $price_lists_ids = $om->search(
                \sale\price\PriceList::getType(),
                [
                    ['price_list_category_id', '=', $line['booking_id.center_id.price_list_category_id']],
                    ['date_from', '<=', $line['booking_line_group_id.date_from']],
                    ['date_to', '>=', $line['booking_line_group_id.date_from']],
                    ['status', 'in', ['pending', 'published']]
                ],
                ['duration' => 'asc']
            );

            $found = false;

            if($price_lists_ids > 0 && count($price_lists_ids)) {
                /*
                    Search for a matching Price within the found Price List
                */
                foreach($price_lists_ids as $price_list_id) {
                    // there should be one or zero matching pricelist with status 'published', if none of the found pricelist
                    $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $line['product_id']] ]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        /*
                            Assign found Price to current line
                        */
                        $found = true;
                        $om->update(get_called_class(), $line_id, ['price_id' => $prices_ids[0]]);

                        // update booking depending on the status of the pricelist
                        $pricelists = $om->read('sale\price\PriceList', $price_list_id, [ 'status' ]);
                        if($pricelists > 0) {
                            $pricelist = reset($pricelists);
                            if($pricelist['status'] == 'pending') {
                                $om->update('sale\booking\Booking', $line['booking_id'], ['is_price_tbc' => true]);
                            }
                        }
                        break;
                    }
                }
            }
            if(!$found) {
                $om->update(get_called_class(), $line_id, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0, 'price' => 0]);
                $date = date('Y-m-d', $line['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for product {$line['product_id']} for date {$date}", QN_REPORT_WARNING);
            }
        }
    }

}