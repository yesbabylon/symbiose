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

            'is_accomodation' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to an accomodation (from product_model).',
                'function'          => 'getIsAccomodation',
                'store'             => true
            ],

            'is_meal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to a meal (from product_model).',
                'function'          => 'getIsMeal',
                'store'             => true
            ],

            'qty_accounting_method' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Quantity accounting method (from product_model).',
                'function'          => 'getQtyAccountingMethod',
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
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their booking).',
                'required'          => true,             // must be set at creation
                'onupdate'          => 'onupdateBookingLineGroupId',
                'ondelete'          => 'cascade'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
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

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Consumption',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Consumptions related to the booking line.',
                'ondetach'          => 'delete'
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineRentalUnitAssignement',
                'foreign_field'     => 'booking_line_id',
                'description'       => "The rental units the line is assigned to.",
                'ondetach'          => 'delete',
                'visible'           => ['is_accomodation', '=', true]
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Price adapters holding the manual discounts applied on the line.',
                'onupdate'          => 'sale\booking\BookingLine::onchangePriceAdaptersIds'
            ]

        ];
    }

    public static function getIsAccomodation($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:getIsAccomodation", QN_REPORT_DEBUG);

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

    public static function getIsMeal($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:getIsMeal", QN_REPORT_DEBUG);

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

    public static function getQtyAccountingMethod($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:getQtyAccountingMethod", QN_REPORT_DEBUG);

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
     * New group assignement : should (only) be called upon creation
     * 
     */
    public static function onupdateBookingLineGroupId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateBookingLineGroupId", QN_REPORT_DEBUG);

    }

    /**
     * Update the price_id according to booking line settings.
     *
     * This is called at booking line creation.
     */
    public static function onupdateProductId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateProductId", QN_REPORT_DEBUG);

        // reset computed fields related to product model
        $om->write(__CLASS__, $oids, ['name' => null, 'qty_accounting_method' => null, 'is_accomodation' => null, 'is_meal' => null]);

        // resolve price_id for new product_id
        $om->call(__CLASS__, '_updatePriceId', $oids, $lang);

        // we might change the product_id but not the quantity : we cannot know if qty is changed during the same operation
        // #memo - in ORM, a check is performed on the onchange methods to prevent handling same event multiple times

        // quantity might depends on the product model AND the sojourn (nb_pers, nb_nights)
        $lines = $om->read(__CLASS__, $oids, [
            'product_id.product_model_id.booking_type',
            'booking_id',
            'qty',
            'has_own_qty',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.nb_nights',
            'is_accomodation',
            'is_meal',
            'qty_accounting_method'
        ], $lang);

        foreach($lines as $lid => $line) {
            // if model of chosen product has a non-generic booking type, update the booking of the line accordingly
            if(isset($line['product_id.product_model_id.booking_type']) && $line['product_id.product_model_id.booking_type'] != 'general') {
                $om->write('lodging\sale\booking\Booking', $line['booking_id'], ['type' => $line['product_id.product_model_id.booking_type']]);
            }
            $qty = $line['qty'];
            if(!$line['has_own_qty']) {
                if($line['qty_accounting_method'] == 'accomodation') {
                    // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the sojourn
                    // which should have been stored in the nb_nights field
                    $qty = $line['booking_line_group_id.nb_nights'];
                }
                else if($line['qty_accounting_method'] == 'person') {
                    // lines having a product 'by person' have a qty assigned to the 'duration' x 'nb_pers' of the sojourn
                    // which should have been stored in the nb_pers field
                    if($line['is_meal'] || $line['is_accomodation']) {
                        $qty = $line['booking_line_group_id.nb_pers'] * max(1, $line['booking_line_group_id.nb_nights']);
                    }
                    else {
                        $qty = $line['booking_line_group_id.nb_pers'];
                    }
                }
            }

            if($qty != $line['qty'] || $line['qty_accounting_method'] == 'accomodation') {
                // make sure qty is updated in order to re-assign the rental units
                $om->write(__CLASS__, $lid, ['qty' => $qty]);
            }
        }

        // reset computed fields related to price
        $om->call('sale\booking\BookingLine', '_resetPrices', $oids, $lang);

    }

    public static function onupdateQtyVars($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateQtyVars", QN_REPORT_DEBUG);
        $lines = $om->read(__CLASS__, $oids, ['booking_line_group_id.nb_pers','qty_vars']);

        if($lines > 0) {
            // set quantities according to qty_vars arrays
            foreach($lines as $lid => $line) {
                $nb_pers = $line['booking_line_group_id.nb_pers'];
                // qty_vars should be a JSON array holding a series of deltas
                $qty_vars = json_decode($line['qty_vars']);
                if($qty_vars) {
                    $qty = 0;
                    foreach($qty_vars as $variation) {
                        $qty += $nb_pers + $variation;
                    }
                    $om->write(__CLASS__, $lid, ['qty' => $qty]);
                }
                else {
                    $om->call(__CLASS__, '_updateQty', $oids, $lang);
                }
            }
        }

        // reset computed fields related to price
        $om->call('sale\booking\BookingLine', '_resetPrices', $oids, $lang);
    }

    /**
     * Update the quantity of products.
     *
     * This handler is called at booking line creation and is in charge of updating the rental units assignments related to the line.
     */
    public static function onupdateQty($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onupdateQty", QN_REPORT_DEBUG);

        // try to auto-assign a rental_unit

        $lines = $om->read(__CLASS__, $oids, [
            'booking_id', 'booking_id.center_id',
            'booking_line_group_id',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.date_from',
            'booking_line_group_id.date_to',
            'product_id',
            'product_id.product_model_id',
            'qty_accounting_method',
            'is_accomodation',
            'rental_unit_assignments_ids'
        ], $lang);

        // drop lines that do not relate to accomodations
        // #todo - we should rather deal with "is_rental_unit" (all rental units should be addressed)
        $lines = array_filter($lines, function($a) { return $a['is_accomodation']; });

        $bookings_ids = [];
        $booking_line_groups_ids = [];
        // there is at least one accomodation
        if(count($lines)) {

            // read all related product models at once
            $product_models_ids = array_map(function($oid) use($lines) {return $lines[$oid]['product_id.product_model_id'];}, array_keys($lines));
            $product_models = $om->read('lodging\sale\catalog\ProductModel', $product_models_ids, [
                'rental_unit_assignement',
                'capacity',
                'rental_unit_category_id',
                'rental_unit_id'
            ], $lang);

            foreach($lines as $lid => $line) {
                $bookings_ids[] = $line['booking_id'];
                $booking_line_groups_ids[] = $line['booking_line_group_id'];

                // remove all previous rental_unit assignements
                $om->write(__CLASS__, $lid, ['rental_unit_assignments_ids' => array_map(function($a) { return "-$a";}, $line['rental_unit_assignments_ids'])]);

                $center_id = $line['booking_id.center_id'];
                $nb_pers = $line['booking_line_group_id.nb_pers'];
                $date_from = $line['booking_line_group_id.date_from'];
                $date_to = $line['booking_line_group_id.date_to'];

                $rental_unit_assignement = $product_models[$line['product_id.product_model_id']]['rental_unit_assignement'];

                // find available rental units (sorted by capacity, desc)
                $rental_units_ids = Consumption::_getAvailableRentalUnits($om, $center_id, $line['product_id'], $date_from, $date_to);
                // retrieve rental units capacities
                $rental_units = [];
                $assigned_rental_units = [];

                if($rental_units_ids > 0 && count($rental_units_ids)) {
                    $rental_units = array_values($om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['id', 'capacity', 'is_accomodation']));
                }

                $found = false;
                // pass-1 - search for an exact capacity match
                for($i = 0, $n = count($rental_units); $i < $n; ++$i) {
                    if($rental_units[$i]['capacity'] == $nb_pers) {
                        $rental_units = [$rental_units[$i]];
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    // handle special case : smallest rental unit has bigger capacity than nb_pers
                    if($nb_pers < $rental_units[$n-1]['capacity']) {
                        $rental_units = [$rental_units[$n-1]];
                    }
                    else {
                        // pass-2 - no exact match, choose between min matching capacity and spreading pers across units
                        $i = 0;
                        while($rental_units[$i]['capacity'] > $nb_pers) {
                            // we should reach at max $n-2
                            ++$i;
                        }
                        $alternate_index = $i-1;
                        $alternate = 0;
                        if($alternate_index >= 0) {
                            $rental_unit = $rental_units[$alternate_index];
                            $alternate = $rental_unit['capacity'];
                        }

                        $collect = [];
                        $list = array_slice($rental_units, $i);

                        $combinations = self::_get_rental_units_combinations($list, $nb_pers, 0, 0, $collect);

                        if(count($combinations)) {
                            $min_index = -1;
                            // $D = abs($alternate - $nb_pers);
                            // favour a single accomodation
                            $D = abs($alternate - $nb_pers) / 2;

                            foreach($combinations as $index => $combination) {
                                // $R = floor($nb_pers / count($combination));
                                $R = count($combination);

                                if($R <= $D) {
                                    if($min_index >= 0) {
                                        if(count($combinations[$min_index]) > count($combination)) {
                                            $min_index = $index;
                                        }
                                    }
                                    else {
                                        $min_index = $index;
                                    }
                                }
                            }
                            // we found at least one combination
                            if($min_index >= 0) {
                                $rental_units = $combinations[$min_index];
                            }
                            else if($alternate_index >= 0) {
                                $rental_units = [$rental_units[$alternate_index]];
                            }
                            else {
                                $rental_units = [];
                            }
                        }
                        else {
                            $rental_units = [];
                        }
                    }
                }

                /*
                    Assign to selected rental units
                */

                $remaining = $nb_pers;

                // min serie for available capacity starts from max(0, i-1)
                for($j = 0, $n = count($rental_units) ;$j < $n; ++$j) {
                    $rental_unit = $rental_units[$j];
                    $assigned = min($rental_unit['capacity'], $remaining);
                    $rental_unit['assigned'] = $assigned;
                    $assigned_rental_units[] = $rental_unit;
                    $remaining -= $assigned;
                    if($remaining <= 0) break;
                }

                if($remaining > 0) {
                    // no availability !
                    $assignement = [
                        'booking_id'        => $line['booking_id'],
                        'booking_line_id'   => $lid,
                        'rental_unit_id'    => 0,
                        'qty'               => $nb_pers
                    ];
                    trigger_error("QN_DEBUG_ORM::no availability", QN_REPORT_DEBUG);
                    $om->create('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignement);
                }
                else {
                    foreach($assigned_rental_units as $rental_unit) {
                        $assignement = [
                            'booking_id'            => $line['booking_id'],
                            'booking_line_id'       => $lid,
                            'booking_line_group_id' => $line['booking_line_group_id'],                            
                            'rental_unit_id'        => $rental_unit['id'],
                            'qty'                   => $rental_unit['assigned'],
                            'is_accomodation'       => $rental_unit['is_accomodation']
                        ];
                        trigger_error("QN_DEBUG_ORM::assigning {$rental_unit['id']}", QN_REPORT_DEBUG);
                        $om->create('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignement);
                    }
                }
            }
        }

        // reset computed fields related to price
        $om->call('sale\booking\BookingLine', '_resetPrices', $oids, $lang);
    }



    /**
     * Check wether an object can be created, and optionally perform additional operations.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        $bookings = $om->read('lodging\sale\booking\Booking', $values['booking_id'], ['status'], $lang);
        $groups = $om->read('lodging\sale\booking\BookingLineGroup', $values['booking_line_group_id'], ['is_extra'], $lang);

        if($bookings > 0 && $groups > 0) {
            $booking = reset($bookings);
            $group = reset($groups);
            
            if(
                in_array($booking['status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                ||
                ($booking['status'] != 'quote' && !$group['is_extra'])
            ) {
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

        $allowed = ['is_contractual', 'is_invoiced'];
        $non_allowed = 0;

        foreach($values as $field => $value) {
            if(!in_array($field, $allowed)) {
                ++$non_allowed;
            }
        }

        if($non_allowed > 0) {
            $lines = $om->read(get_called_class(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra'], $lang);            
            if($lines > 0) {
                foreach($lines as $line) {
                    if(
                        in_array($line['booking_id.status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                        ||
                        ($line['booking_id.status'] != 'quote' && !$line['booking_line_group_id.is_extra'])
                    ) {
                        return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
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
     * @return boolean  Returns true if the object can be deleted, or false otherwise.
     */
    public static function candelete($om, $oids) {
        $lines = $om->read(get_called_class(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra']);

        if($lines > 0) {
            foreach($lines as $line) {
                if(
                    in_array($line['booking_id.status'], ['invoiced', 'debit_balance', 'credit_balance', 'balanced'])
                    ||
                    ($line['booking_id.status'] != 'quote' && !$line['booking_line_group_id.is_extra'])
                ) {
                    return ['status' => ['non_editable' => 'Non-extra service lines cannot be changed for non-quote bookings.']];
                }
            }
        }

        return parent::candelete($om, $oids);
    }

    /**
     * Update the quantity according to parent group (pack_id, nb_pers, nb_nights) and variation array.
     * This method is triggered on fields update from BookingLineGroup.
     *
     */
    public static function _updateQty($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updateQty", QN_REPORT_DEBUG);

        $lines = $om->read(__CLASS__, $oids, [
            'has_own_qty',
            'qty_vars',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.nb_nights',
            'product_id.product_model_id.qty_accounting_method',
            'product_id.product_model_id.is_accomodation',
            'product_id.product_model_id.is_meal',
            'product_id.product_model_id.has_duration',
            'product_id.product_model_id.duration'
        ], $lang);

        if($lines > 0) {
            foreach($lines as $lid => $line) {
                if(!$line['has_own_qty']) {
                    if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                        $om->write(__CLASS__, $lid, ['qty' => $line['booking_line_group_id.nb_nights']]);
                    }
                    else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                        if(!$line['qty_vars']) {
                            $factor = 1;
                            if($line['product_id.product_model_id.has_duration']) {
                                $factor = $line['product_id.product_model_id.duration'];
                            }
                            else if($line['product_id.product_model_id.is_accomodation'] || $line['product_id.product_model_id.is_meal'] ) {
                                $factor = max(1, $line['booking_line_group_id.nb_nights']);
                            }
                            $om->write(__CLASS__, $lid, ['qty' => $line['booking_line_group_id.nb_pers'] * $factor]);
                        }
                        else {
                            $qty_vars = json_decode($line['qty_vars']);
                            // qty_vars is set and valid
                            if($qty_vars) {
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
                                $om->write(__CLASS__, $lid, ['qty_vars' => json_encode($qty_vars)]);
                                // will trigger onupdateQtyVar which will update  qty
                            }
                        }
                    }
                }
                else {
                    // own quantity has been assigned in onupdateProductId
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
    public static function _updatePriceId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updatePriceId", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, [
            'booking_line_group_id.date_from',
            'product_id',
            'booking_id',
            'booking_id.center_id.price_list_category_id'
        ]);

        foreach($lines as $line_id => $line) {
            /*
                Find the Price List that matches the criteria from the booking with the shortest duration
            */
            $price_lists_ids = $om->search(
                'sale\price\PriceList',
                [
                    ['price_list_category_id', '=', $line['booking_id.center_id.price_list_category_id']],
                    ['date_from', '<=', $line['booking_line_group_id.date_from']],
                    ['date_to', '>=', $line['booking_line_group_id.date_from']],
                    ['status', 'in', ['pending', 'published']]
                ],
                ['is_active' => 'desc']
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
                        $om->write(get_called_class(), $line_id, ['price_id' => $prices_ids[0]]);

                        // update booking depending on the status of the pricelist
                        $pricelists = $om->read('sale\price\PriceList', $price_list_id, [ 'status' ]);
                        if($pricelists > 0) {
                            $pricelist = reset($pricelists);
                            if($pricelist['status'] == 'pending') {
                                $om->write('sale\booking\Booking', $line['booking_id'], ['is_price_tbc' => true]);
                            }
                        }
                        break;
                    }
                }
            }
            if(!$found) {
                $om->write(get_called_class(), $line_id, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0, 'price' => 0]);
                $date = date('Y-m-d', $line['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for product {$line['product_id']} for date {$date}", QN_REPORT_ERROR);
            }
        }
    }

    /**
     *
     * This method is called upon setting booking status to 'option' or 'confirmed' (#see `option.php`)
     * #memo - consumptions are used in the planning.
     *
     */
    public static function _createConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_createConsumptions", QN_REPORT_DEBUG);

        /*
            get in-memory list of consumptions for all lines
        */
        $consumptions = $om->call(__CLASS__, '_getResultingConsumptions', $oids, $lang);

        /*
            create consumptions objects
        */

        // map of consumptions ids for each booking_line_id
        $lines_consumptions_ids = [];
        foreach($consumptions as $consumption) {

            $cid = $om->create('lodging\sale\booking\Consumption', $consumption, $lang);
            if($cid > 0) {
                $booking_line_id = $consumption['booking_line_id'];
                if(!isset($lines_consumptions_ids[$booking_line_id])) {
                    $lines_consumptions_ids[$booking_line_id] = [];
                }
                $lines_consumptions_ids[$booking_line_id][] = $cid;
            }
        }

        foreach($lines_consumptions_ids as $lid => $consumptions_ids) {
            $om->write(__CLASS__, $lid, ['consumptions_ids' => $consumptions_ids ]);
        }

    }



    /**
     * Process targeted BookingLines to create an in-memory list of consumptions objects.
     *
     */
    public static function _getResultingConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_getResultingConsumptions", QN_REPORT_DEBUG);

        // resulting consumptions objects
        $consumptions = [];

        $lines = $om->read(__CLASS__, $oids, [
            'product_id', 'qty', 'qty_vars',
            'booking_id', 'booking_id.center_id',
            'booking_line_group_id', 'booking_line_group_id.nb_pers', 'booking_line_group_id.nb_nights', 'booking_line_group_id.date_from',
            'consumptions_ids',
            'product_id.product_model_id'
        ], $lang);

        // read all related product models at once
        $product_models_ids = array_map(function($oid) use($lines) {return $lines[$oid]['product_id.product_model_id'];}, array_keys($lines));
        $product_models = $om->read('lodging\sale\catalog\ProductModel', $product_models_ids, [
            'type',
            'service_type',
            'schedule_offset',
            'schedule_type',
            'schedule_default_value',
            'qty_accounting_method',
            'has_duration',
            'duration',
            'is_accomodation',
            'is_meal'
        ]);

        if($lines > 0 && count($lines)) {
            foreach($lines as $lid => $line) {
                /*
                    Reset consumptions (updating consumptions_ids will trigger ondetach event)
                */
                $om->write(__CLASS__, $lid, ['consumptions_ids' => array_map(function($a) { return "-$a";}, $line['consumptions_ids'])]);

                if($line['qty'] <= 0) continue;

                $nb_pers    = $line['booking_line_group_id.nb_pers'];
                $nb_nights  = $line['booking_line_group_id.nb_nights'];

                /*
                    Create consumptions according to line product and quantity
                */
                $product_type = $product_models[$line['product_id.product_model_id']]['type'];
                $service_type = $product_models[$line['product_id.product_model_id']]['service_type'];
                $has_duration = $product_models[$line['product_id.product_model_id']]['has_duration'];

                // consumptions are schedulable services
                if($product_type == 'service' && $service_type == 'schedulable') {

                    // retrieve default time for consumption
                    list($hour_from, $minute_from, $hour_to, $minute_to) = [12, 0, 13, 0];
                    $schedule_default_value = $product_models[$line['product_id.product_model_id']]['schedule_default_value'];
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

                    $is_meal = $product_models[$line['product_id.product_model_id']]['is_meal'];
                    // is_accomodation is used as "is it attached to a rental_unit (accomodation or other)"
                    $is_rental_unit = $product_models[$line['product_id.product_model_id']]['is_accomodation'];
                    $qty_accounting_method = $product_models[$line['product_id.product_model_id']]['qty_accounting_method'];

                    // number of consumptions differs for accomodations (rooms are occupied nb_nights + 1 until sometime in the morning)
                    $nb_products = $nb_nights;
                    $nb_times = $nb_pers;

                    if($is_rental_unit) {
                        // #todo - we should check if the rental unit is an acoomodation
                        ++$nb_products; // checkout is done the day following the last night

                        /*
                            retrieve assigned rental units
                            rental units have been assigned during booking in BookingLineRentalUnitAssignement objects
                        */

                        $assignments_ids = $om->search('lodging\sale\booking\BookingLineRentalUnitAssignement', ['booking_line_id', '=', $lid]);
                        $rental_units_assignments = $om->read('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignments_ids, ['rental_unit_id','qty']);

                    }
                    else if($has_duration) {
                        $nb_products = $product_models[$line['product_id.product_model_id']]['duration'];
                    }

                    if($qty_accounting_method == 'accomodation') {
                        $nb_times = 1;  // an accomodation is accounted independently from the number of persons
                    }

                    list($day, $month, $year) = [ date('j', $line['booking_line_group_id.date_from']), date('n', $line['booking_line_group_id.date_from']), date('Y', $line['booking_line_group_id.date_from']) ];
                    // fetch the offset, in days, for the scheduling
                    $offset = $product_models[$line['product_id.product_model_id']]['schedule_offset'];

                    $days_nb_times = array_fill(0, $nb_products, $nb_times);
                    if( $qty_accounting_method == 'person' && ($nb_times * $nb_products) != $line['qty']) {
                        // $nb_times varies from one day to another : load specific days_nb_times array
                        $qty_vars = json_decode($line['qty_vars']);
                        // qty_vars is set and valid
                        if($qty_vars) {
                            $i = 0;
                            foreach($qty_vars as $variation) {
                                $days_nb_times[$i] = $nb_times + $variation;
                                ++$i;
                            }
                            // handle last day for acccomodations
                            if($is_rental_unit) {
                                // #todo - we should check if related rental_unit is an accomodation
                                $days_nb_times[$i] = $nb_times + $variation;
                            }
                        }
                    }

                    /*
                        retrieve all involved rental units (limited to 2 levels above and 2 levels below)
                    */
                    $rental_units = [];
                    $rental_units_ids = [];
                    if($rental_units_assignments > 0) {
                        $rental_units_ids = array_map(function ($a) { return $a['rental_unit_id']; }, array_values($rental_units_assignments));

                        // fetch 2 levels of rental units identifiers
                        for($i = 0; $i < 2; ++$i) {                        
                            $units = $om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['parent_id', 'children_ids']);
                            if($units > 0) {
                                foreach($units as $uid => $unit) {
                                    if($unit['parent_id'] > 0) {
                                        $rental_units_ids[] = $unit['parent_id'];
                                    }
                                    if(count($unit['children_ids'])) {
                                        foreach($unit['children_ids'] as $uid) {
                                            $rental_units_ids[] = $uid;
                                        }
                                    }
                                }
                            }
                        }                        
                        // read all involved rental units
                        $rental_units = $om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['parent_id', 'children_ids', 'can_partial_rent', 'is_accomodation']);
                    }


                    // $nb_products represent each day of the stay
                    for($i = 0; $i < $nb_products; ++$i) {
                        $c_date = mktime(0, 0, 0, $month, $day+$i+$offset, $year);
                        $c_schedule_from = $schedule_from;
                        $c_schedule_to = $schedule_to;

                        // create as many consumptions as the number of rental units assigned to the line
                        if($is_rental_unit) {
                            // if day is not the arrival day
                            if($i > 0) {
                                $c_schedule_from = 0;               // midnight same day
                            }

                            if($i == $nb_nights) {                  // last day
                                $c_schedule_to = $schedule_to;
                            }
                            else {
                                $c_schedule_to = 24 * 3600;         // midnight next day
                            }

                            if($rental_units_assignments > 0) {
                                foreach($rental_units_assignments as $assignment) {
                                    $rental_unit_id = $assignment['rental_unit_id'];
                                    $consumption = [
                                        'booking_id'            => $line['booking_id'],
                                        'center_id'             => $line['booking_id.center_id'],
                                        'booking_line_group_id' => $line['booking_line_group_id'],
                                        'booking_line_id'       => $lid,
                                        'date'                  => $c_date,
                                        'schedule_from'         => $c_schedule_from,
                                        'schedule_to'           => $c_schedule_to,
                                        'product_id'            => $line['product_id'],
                                        'is_rental_unit'        => true,
                                        'is_meal'               => $is_meal,
                                        'rental_unit_id'        => $rental_unit_id,
                                        'qty'                   => $assignment['qty'],
                                        'type'                  => 'book'
                                    ];
                                    $consumptions[] = $consumption;

                                    // 1) recurse through children : all child units aer blocked as 'link'
                                    $children_ids = [];
                                    $children_stack = $rental_units[$rental_unit_id]['children_ids'];
                                    while(count($children_stack)) {
                                        $unit_id = array_pop($children_stack);
                                        $children_ids[] = $unit_id;
                                        if(isset($rental_units[$unit_id]) && $rental_units[$unit_id]['children_ids']) {
                                            foreach($units[$unit_id]['children_ids'] as $child_id) {
                                                $children_stack[] = $child_id;
                                            }
                                        }
                                    }
                                    $consumption['type'] = 'link';
                                    foreach($children_ids as $child_id) {
                                        $consumption['rental_unit_id'] = $child_id;
                                        $consumptions[] = $consumption;
                                    }
                                    // 2) loop through parents : if a parent has 'can_partial_rent', it is partially blocked as 'part', otherwise fully blocked as 'link'
                                    $parents_ids = [];
                                    $unit_id = $rental_unit_id;
                                    while( isset($rental_units[$unit_id]) ) {
                                        $parent_id = $rental_units[$unit_id]['parent_id'];
                                        if($parent_id > 0) {
                                            $parents_ids[] = $parent_id;
                                        }
                                        $unit_id = $parent_id;
                                    }
                                    foreach($parents_ids as $parent_id) {
                                        $consumption['type'] = ($rental_units[$parent_id]['can_partial_rent'])?'part':'link';
                                        $consumption['rental_unit_id'] = $parent_id;
                                        $consumptions[] = $consumption;
                                    }
                                }
                            }
                        }
                        // create a single consumption with the quantity set accordingly (may vary from one day to another)
                        else {
                            $consumptions[] = [
                                'booking_id'            => $line['booking_id'],
                                'center_id'             => $line['booking_id.center_id'],
                                'booking_line_group_id' => $line['booking_line_group_id'],
                                'booking_line_id'       => $lid,
                                'date'                  => $c_date,
                                'schedule_from'         => $c_schedule_from,
                                'schedule_to'           => $c_schedule_to,
                                'product_id'            => $line['product_id'],
                                'is_rental_unit'        => false,
                                'is_meal'               => $is_meal,
                                'qty'                   => $days_nb_times[$i],
                                'type'                  => 'book'
                            ];
                        }
                    }

                }
            }


        }

        return $consumptions;
    }


    public static function _get_rental_units_combinations($list, $target, $start, $sum, $collect) {
        $result = [];

        // current sum matches target
        if($sum == $target) {
            return [$collect];
        }

        // try sub-combinations
        for($i = $start, $n = count($list); $i < $n; ++$i) {

            // check if the sum exceeds target
            if( ($sum + $list[$i]['capacity']) > $target ) {
                continue;
            }

            // check if it is repeated or not
            if( ($i > $start) && ($list[$i]['capacity'] == $list[$i-1]['capacity']) ) {
                continue;
            }

            // take the element into the combination
            $collect[] = $list[$i];

            // recursive call
            $res = self::_get_rental_units_combinations($list, $target, $i + 1, $sum + $list[$i]['capacity'], $collect);

            if(count($res)) {
                foreach($res as $r) {
                    $result[] = $r;
                }
            }

            // Remove element from the combination
            array_pop($collect);
        }

        return $result;
    }

}