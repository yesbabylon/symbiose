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
                'onchange'          => 'lodging\sale\booking\BookingLine::onchangeQty',
                'default'           => 1.0
            ],

            'is_accomodation' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to an accomodation (from product_model).',
                'function'          => 'lodging\sale\booking\BookingLine::getIsAccomodation',
                'store'             => true
            ],

            'is_meal' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Line relates to a meal (from product_model).',
                'function'          => 'lodging\sale\booking\BookingLine::getIsMeal',
                'store'             => true
            ],

            'qty_accounting_method' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Quantity accounting method (from product_model).',
                'function'          => 'lodging\sale\booking\BookingLine::getQtyAccountingMethod',
                'store'             => true
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their booking).',
                'required'          => true,             // must be set at creation
                'onchange'          => 'lodging\sale\booking\BookingLine::onchangeBookingLineGroupId',
                'ondelete'          => 'cascade'         // delete line when parent group is deleted
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete line when parent booking is deleted
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'onchange'          => 'lodging\sale\booking\BookingLine::onchangeProductId'
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
                'onchange'          => 'sale\booking\BookingLine::onchangePriceAdaptersIds'
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
     * Adapt quantity based on product type and parent group config
     */
    public static function onchangeBookingLineGroupId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onchangeBookingLineGroupId", QN_REPORT_DEBUG);
        $lines = $om->read(__CLASS__, $oids, ['booking_line_group_id.has_pack', 'product_id.product_model_id.qty_accounting_method'], $lang);

        foreach($lines as $lid => $line) {
//#todo - is this necessary ?
        }

    }

    /**
     * Update the price_id according to booking line settings.
     *
     * This is called at booking line creation.
     */
    public static function onchangeProductId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onchangeProductId", QN_REPORT_DEBUG);

        // reset computed fields related to product model
        $om->write(__CLASS__, $oids, ['qty_accounting_method' => null, 'is_accomodation' => null, 'is_meal' => null]);

        // resolve price_id for new product_id
        self::_updatePriceId($om, $oids, $lang);

        // we might change the product_id but not the quantity : we cannot know if qty is changed during the same operation
        // #memo - in ORM, a check is performed on the onchange methods to prevent handling same event multiple times

        // quantity might depends on the product model AND the sojourn (nb_pers, nb_nights)
        $lines = $om->read(__CLASS__, $oids, [
            'qty',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.nb_nights',
            'qty_accounting_method'
        ], $lang);

        foreach($lines as $lid => $line) {
            $qty = $line['qty'];
            if($line['qty_accounting_method'] == 'accomodation') {
                // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the sojourn
                // which should have been stored in the nb_nights field
                $qty = $line['booking_line_group_id.nb_nights'];
            }
            else if($line['qty_accounting_method'] == 'person') {
                // lines having a product 'by person' have a qty assigned to the 'duration' x 'nb_pers' of the sojourn
                // which should have been stored in the nb_pers field
                $qty = $line['booking_line_group_id.nb_pers']  * max(1, $line['booking_line_group_id.nb_nights']);
            }

            if($qty != $line['qty'] || $line['qty_accounting_method'] == 'accomodation') {
                // make sure qty is updated in order to re-assign the rental units
                $om->write(__CLASS__, $lid, ['qty' => $qty]);
            }
        }

    }

    /**
     * Update the quantity of products.
     *
     * This handler is called at booking line creation and is in charge of updating the consumptions related to the line.
     */
    public static function onchangeQty($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onchangeQty", QN_REPORT_DEBUG);

        // try to auto-assign a rental_unit

        $lines = $om->read(__CLASS__, $oids, [
            'booking_id', 'booking_id.center_id',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.date_from',
            'booking_line_group_id.date_to',
            'product_id.product_model_id',
            'qty_accounting_method',
            'is_accomodation',
            'rental_unit_assignments_ids'
        ], $lang);

        // drop lines that do not relate to accomodations
        $lines = array_filter($lines, function($a) { return $a['is_accomodation']; });

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
                // remove all previous rental_unit assignements
                $om->write(__CLASS__, $lid, ['rental_unit_assignments_ids' => array_map(function($a) { return "-$a";}, $line['rental_unit_assignments_ids'])]);

                $center_id = $line['booking_id.center_id'];
                $capacity = $product_models[$line['product_id.product_model_id']]['capacity'];
                $nb_pers = $line['booking_line_group_id.nb_pers'];
                $date_from = $line['booking_line_group_id.date_from'];
                $date_to = $line['booking_line_group_id.date_to'];


                $rental_unit_assignement = $product_models[$line['product_id.product_model_id']]['rental_unit_assignement'];

                if($rental_unit_assignement == 'unit') {
                    $rental_unit_id = $product_models[$line['product_id.product_model_id']]['rental_unit_id'];
                    $assignement = [
                        'booking_id'        => $line['booking_id'],
                        'booking_line_id'   => $lid,
                        'rental_unit_id'    => $rental_unit_id,
                        'qty'               => $nb_pers
                    ];
                    trigger_error("QN_DEBUG_ORM::assigning {$rental_unit_id}", QN_REPORT_DEBUG);
                    $om->create('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignement);
                }
                else if($rental_unit_assignement == 'category') {
                    trigger_error("QN_DEBUG_ORM::assignment by category", QN_REPORT_DEBUG);
                    // #todo
                    $rental_unit_category_id = $product_models[$line['product_id.product_model_id']]['rental_unit_category_id'];
                    // rental_unit = this.available_rental_units.find(unit => (unit.category_id && unit.category_id.id == product.product_model_id.rental_unit_category_id.id));
                }
                else if($rental_unit_assignement == 'capacity') {
                    trigger_error("QN_DEBUG_ORM::assignment by capacity", QN_REPORT_DEBUG);                    
                    // find available rental units

                    // retrieve list of possible rental_units based on center_id having max nb_pers (we try to assign people of a same group in a same accomodation)
                    $rental_units_ids = $om->search('lodging\realestate\RentalUnit', [ ['center_id', '=', $center_id], ['capacity', '<=', $nb_pers] ]);
                    // retrieve existing consumptions for selected center occuring between chosen dates relating to rental_units
                    $consumptions_ids = $om->search('lodging\sale\booking\Consumption', [ ['is_rental_unit', '=', true], ['center_id', '=', $center_id], ['date', '>=', $date_from], ['date', '<=', $date_to]]);
                    if($consumptions_ids > 0 && count($consumptions_ids)) {
                        $consumptions = $om->read('lodging\sale\booking\Consumption', $consumptions_ids, ['rental_unit_id']);
                        $booked_rental_units_ids = array_map(function($a) {return $a['rental_unit_id'];}, $consumptions);
                        // remove from rental_units list the ones that are already assigned
                        $rental_units_ids = array_filter($rental_units_ids, function($rental_unit_id) use($booked_rental_units_ids) {return !in_array($rental_unit_id, $booked_rental_units_ids);});
                    }

                    if(!count($rental_units_ids)) {
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

                        // retrieve rental units capacities
                        $rental_units = $om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['id', 'capacity']);

                        // sort rental units by descending capacities
                        usort($rental_units, function($a, $b) {
                            return $b['capacity'] - $a['capacity'];
                        });

                        $last_index = count($rental_units) - 1;

                        $remainder = $nb_pers;

                        foreach($rental_units as $index => $rental_unit) {

                            if($rental_unit['capacity'] > $remainder && $index != $last_index ) {
                                continue;
                            }

                            $assigned = ($rental_unit['capacity'] > $remainder)?$remainder:$rental_unit['capacity'];
                            $remainder -= $assigned;

                            $assignement = [
                                'booking_id'        => $line['booking_id'],
                                'booking_line_id'   => $lid,
                                'rental_unit_id'    => $rental_unit['id'],
                                'qty'               => $assigned
                            ];
                            trigger_error("QN_DEBUG_ORM::assigning {$rental_unit['id']}", QN_REPORT_DEBUG);
                            $om->create('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignement);
                            if($remainder <= 0) break;
                        }

                    }
                }

            }

        }

        // reset total price
        $om->write(__CLASS__, $oids, ['price' => null]);

    }


    /**
     * Update booking line quantities according to the newly set pack_id.
     * Booking lines have been created, but their qty not necessarily set.
     *
     * pack_id refers to the parent booking_line_group_id.pack_id (there is no pack_id in BookingLine schema)
     *
     * This method is called by BookingLineGroup::onchangePackId (and derived classes overloads)
     * Should be called upon change on: group pack_id, nb_pers, nb_nights
     */
    public static function _updatePack($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updatePack", QN_REPORT_DEBUG);

        // read product_model from parent group pack_id
        $lines = $om->read(__CLASS__, $oids, [
            'has_own_qty',
            'booking_line_group_id.nb_pers',
            'booking_line_group_id.nb_nights',
            'booking_line_group_id.pack_id.product_model_id',
            'product_id.product_model_id.qty_accounting_method'
        ], $lang);

        foreach($lines as $lid => $line) {
            if(!$line['has_own_qty']) {
                //default quantity (applicable for qty_accounting_method == 'unit' [with has_ own_qty set to false])
                $qty = 1;
                if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                    // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the sojourn
                    // which should have been stored in the nb_nights field
                    $qty = $line['booking_line_group_id.nb_nights'];
                }
                else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                    // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the sojourn
                    // which should have been stored in the nb_pers field
                    $qty = $line['booking_line_group_id.nb_pers']  * max(1, $line['booking_line_group_id.nb_nights']);
                }

                // will trigger rental units assignement
                $om->write(__CLASS__, $lid, ['qty' => $qty ]);
            }
            else {
                // own quantity has been assigned in onchangeProductId
            }
        }

    }


    /**
     * Try to assign the price_id according to the current product_id.
     * Resolve the price from the applicable price lists, based on booking_line_group settings and booking center.
     *
     * _updatePriceId is also called upon booking_id.center_id and booking_line_group_id.date_from changes.
     */
    public static function _updatePriceId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updatePriceId", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, [
            'booking_line_group_id.date_from',
            'product_id',
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
                    ['date_to', '>=', $line['booking_line_group_id.date_from']]
                ],
                ['duration' => 'asc']
            );

            $found = false;

            if($price_lists_ids > 0 && count($price_lists_ids)) {
                /*
                    Search for a matching Price within the found Price List
                */
                foreach($price_lists_ids as $price_list_id) {
                    // there should be exactly one matching price
                    $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $line['product_id']] ]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        /*
                            Assign found Price to current line
                        */
                        $found = true;
                        $om->write(get_called_class(), $line_id, ['price_id' => $prices_ids[0]]);
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
     * This method is called upon change on: qty (self::onchangeQty)
     * (consumptions are used in the planning)
     * change on the satus => option or confirmed
     *
     */
    public static function _createConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_createConsumptions", QN_REPORT_DEBUG);

        /*
            get in-memory list of consumptions for all lines
        */

        $consumptions = self::_getResultingConsumptions($om, $oids, $lang);

        /*
            create consumptions objects
        */

        // map of consumptions ids for each booking_line_id
        $consumptions_ids = [];
        foreach($consumptions as $consumption) {

            $cid = $om->create('lodging\sale\booking\Consumption', $consumption, $lang);
            if($cid > 0) {
                $booking_line_id = $consumption['booking_line_id'];
                if(!isset($consumptions_ids[$booking_line_id])) {
                    $consumptions_ids[$booking_line_id] = [];
                }
                $consumptions_ids[$booking_line_id][] = $cid;
            }
        }

        foreach($consumptions_ids as $lid => $consumptions_ids) {
            $om->write(__CLASS__, $lid, ['consumptions_ids' => [$consumptions_ids] ]);
        }

    }





    /**
     * Process targeted BookineLines to create an in-memory list of consumptions objects.
     *
     */
    public static function _getResultingConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_createConsumptions", QN_REPORT_DEBUG);

        // resulting consumptions objects
        $consumptions = [];

        $lines = $om->read(__CLASS__, $oids, [
            'product_id', 'qty',
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
                    $is_accomodation = $product_models[$line['product_id.product_model_id']]['is_accomodation'];
                    $qty_accounting_method = $product_models[$line['product_id.product_model_id']]['qty_accounting_method'];

                    $rental_unit_id = 0;

                    // number of consumptions differs for accomodations (rooms are occupied nb_nights + 1 until sometime in the morning)
                    $nb_products = $nb_nights;
                    $nb_times = $nb_pers;

                    if($is_accomodation) {
                        ++$nb_products; // checkout is done the day following the last night

                        /*
                            retrieve all assigned rental units
                            rental units have been assigned during booking in BookingLineRentalUnitAssignement objects
                        */

                        $rental_units = [];
                        $assignments_ids = $om->search('lodging\sale\booking\BookingLineRentalUnitAssignement', ['booking_line_id', '=', $lid]);
                        $assignments = $om->read('lodging\sale\booking\BookingLineRentalUnitAssignement', $assignments_ids, ['rental_unit_id','qty']);

                        foreach($assignments as $aid => $assignement) {
                            $rental_units[$assignement['rental_unit_id']] = $assignement['qty'] * $nb_products;
                        }

                    }

                    if($qty_accounting_method == 'accomodation') {
                        $nb_times = 1;  // an accomodation is accounted independently from the number of persons
                    }

                    list($day, $month, $year) = [ date('j', $line['booking_line_group_id.date_from']), date('n', $line['booking_line_group_id.date_from']), date('Y', $line['booking_line_group_id.date_from']) ];
                    $offset = $product_models[$line['product_id.product_model_id']]['schedule_offset'];

                    for($n = 0; $n < $nb_times; ++$n) {
                        for($i = 0; $i < $nb_products; ++$i) {
                            $c_date = mktime(0, 0, 0, $month, $day+$i+$offset, $year);
                            $c_schedule_from = $schedule_from;
                            $c_schedule_to = $schedule_to;

                            $rental_unit_id = 0;

                            if($is_accomodation) {
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

                                // pick amongst the attached rental_units
                                foreach($rental_units as $rid => $qty) {
                                    $rental_unit_id = $rid;
                                    if($qty == 1) {
                                        // it was last place available : remove the rental_unit from the candidates list
                                        unset($rental_units[$rid]);
                                    }
                                    else {
                                        // withdraw one place from the rental unit
                                        --$rental_units[$rid];
                                    }
                                }
                            }

                            $consumptions[] = [
                                'booking_id'            => $line['booking_id'],
                                'center_id'             => $line['booking_id.center_id'],
                                'booking_line_group_id' => $line['booking_line_group_id'],
                                'booking_line_id'       => $lid,
                                'date'                  => $c_date,
                                'schedule_from'         => $c_schedule_from,
                                'schedule_to'           => $c_schedule_to,
                                'product_id'            => $line['product_id'],
                                'is_rental_unit'        => $is_accomodation,
                                'is_meal'               => $is_meal,
                                'rental_unit_id'        => $rental_unit_id
                            ];

                        }
                    }

                }
            }


        }

        return $consumptions;
    }



}