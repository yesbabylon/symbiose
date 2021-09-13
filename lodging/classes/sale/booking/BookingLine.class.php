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
                'onchange'          => 'lodging\sale\booking\BookingLine::onchangeQty'
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
                'ondelete'          => 'cascade',        // delete line when parent group is deleted
                'required'          => true              // must be set at creation
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'onchange'          => 'lodging\sale\booking\BookingLine::onchangeProductId'
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the line is assigned to.",
                "visible"           => ['qty_accounting_method', '=', 'accomodation']
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

    public static function getQtyAccountingMethod($om, $oids, $lang) {
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
     * Update the price_id according to booking line settings.
     * 
     * This is called at booking line creation.
     */
    public static function onchangeProductId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:onchangeProductId", QN_REPORT_DEBUG);
        self::_updatePriceId($om, $oids, $lang);
        // reset quantity accounting method
        $om->write(__CLASS__, $oids, ['qty_accounting_method' => null]);

        // try to auto-assign a rental_unit
        $lines = $om->read(get_called_class(), $oids, ['booking_id.center_id', 'product_id.product_model_id', 'qty_accounting_method'], $lang);

        // read all related product models at once
        $product_models_ids = array_map(function($oid) use($lines) {return $lines[$oid]['product_id.product_model_id'];}, array_keys($lines));
        $product_models = $om->read('lodging\sale\catalog\ProductModel', $product_models_ids, [
            'rental_unit_assignement',
            'capacity',
            'rental_unit_category_id',
            'rental_unit_id'
        ], $lang);

        foreach($lines as $lid => $line) {
            if($line['qty_accounting_method'] == 'accomodation') {

                $rental_unit_id = 0;

                $rental_unit_assignement = $product_models[$line['product_id.product_model_id']]['rental_unit_assignement'];

                if($rental_unit_assignement == 'unit') {
                    $rental_unit_id = $product_models[$line['product_id.product_model_id']]['rental_unit_id'];
                }
                else if($rental_unit_assignement == 'capacity') {
                    $capacity = $product_models[$line['product_id.product_model_id']]['capacity'];
                    // search amongst available rental unit (same center) with capacity >= capacity
                    $rental_units_ids = $om->search('lodging\realestate\RentalUnit', [ ['center_id', '=', $line['booking_id.center_id']], ['capacity', '>=', $capacity] ]);
                    if($rental_units_ids < 0 || !count($rental_units_ids)) {
                        trigger_error("QN_DEBUG_ORM::unable to find a matching rental unit for line {$lid}", QN_REPORT_ERROR);
                    }
                    else {
                        $rental_unit_id = array_shift($rental_units_ids);
                    }
                }
                // assignements == 'category'
                else {

                    $rental_unit_category_id = $product_models[$line['product_id.product_model_id']]['rental_unit_category_id'];
            // #todo
                    // search amongst rental unit, select the first match
                    // rental_unit = this.available_rental_units.find(unit => (unit.category_id && unit.category_id.id == product.product_model_id.rental_unit_category_id.id));
                }

                $om->write(__CLASS__, $oids, ['rental_unit_id' => $rental_unit_id]);
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
        // reset total price
        $om->write(__CLASS__, $oids, ['price' => null]);
        // update consumptions
        self::_updateConsumptions($om, $oids, $lang);
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
                //default quantity
                $qty = 1;
                if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                    // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the product_model (cannot be changewhile group is_locked)
                    // which should have been stored in the nb_nights field
                    $qty = $line['booking_line_group_id.nb_nights'];
                }
                else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                    // lines having a product 'by accomodation' have a qty assigned to the 'duration' of the product_model (cannot be changewhile group is_locked)
                    // which should have been stored in the nb_pers field
                    $qty = $line['booking_line_group_id.nb_pers'];
                }

                // will trigger a call to _updateConsumptions
                $om->write(__CLASS__, $lid, ['qty' => $qty ]);
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
        $lines = $om->read(get_called_class(), $oids, [
            'booking_line_group_id.date_from',
            'product_id',
            'booking_id.center_id.price_list_category_id'
        ]);

        foreach($lines as $line_id => $line) {
            /*
                Find the first Price List that matches the criteria from the booking
            */
            $price_lists_ids = $om->search('sale\price\PriceList', [
                                                                       ['price_list_category_id', '=', $line['booking_id.center_id.price_list_category_id']],
                                                                       ['date_from', '<=', $line['booking_line_group_id.date_from']],
                                                                       ['date_to', '>=', $line['booking_line_group_id.date_from']]
                                                                   ]);
            $price_lists = $om->read('sale\price\PriceList', $price_lists_ids, ['id']);
            $price_list_id = 0;
            if($price_lists > 0 && count($price_lists)) {
                $price_list_id = array_keys($price_lists)[0];
            }
            /*
                Search for a matching Price within the found Price List
            */
            if($price_list_id) {
                // there should be exactly one matching price
                $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $line['product_id']] ]);
                if($prices_ids > 0 && count($prices_ids)) {
                    /*
                        Assign found Price to current line
                    */
                    $om->write(get_called_class(), $line_id, ['price_id' => $prices_ids[0]]);
                }
                else {
                    $om->write(get_called_class(), $line_id, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0, 'price' => 0]);
                    trigger_error("QN_DEBUG_ORM::no matching price found for product {$line['product_id']} in price_list $price_list_id", QN_REPORT_ERROR);
                }
            }
            else {
                $om->write(get_called_class(), $line_id, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0, 'price' => 0]);
                $date = date('Y-m-d', $line['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for date {$date}", QN_REPORT_ERROR);
            }
        }
    }

    /**
     * This method is called upon change on: qty
     */
    public static function _updateConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLine:_updateConsumptions", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, [
            'product_id', 'qty', 'rental_unit_id',
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
            'qty_accounting_method'
        ], $lang);

        if($lines > 0 && count($lines)) {
            foreach($lines as $lid => $line) {
                /*
                    Reset consumptions (updating consumptions_ids will trigger ondetach event)
                */
                $om->write(get_called_class(), $lid, ['consumptions_ids' => array_map(function($a) { return "-$a";}, $line['consumptions_ids'])]);

                if($line['qty'] <= 0) continue;

                $qty        = $line['qty'];
                $nb_pers    = $line['booking_line_group_id.nb_pers'];
                $nb_nights  = $line['booking_line_group_id.nb_nights'];

                /*
                    Create consumptions according to line product and quantity
                */
                $product_type = $product_models[$line['product_id.product_model_id']]['type'];
                $service_type = $product_models[$line['product_id.product_model_id']]['service_type'];

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

                    $consumptions_ids = [];
                    $qty_accounting_method = $product_models[$line['product_id.product_model_id']]['qty_accounting_method'];

                    $is_rental_unit = false;
                    $rental_unit_id = 0;

                    if($qty_accounting_method == 'accomodation') {
                        $qty = 1;
                        $is_rental_unit = true;
                        $rental_unit_id = $line['rental_unit_id'];
                    }

                    list($day, $month, $year) = [ date('j', $line['booking_line_group_id.date_from']), date('n', $line['booking_line_group_id.date_from']), date('Y', $line['booking_line_group_id.date_from']) ];
                    $offset = $product_models[$line['product_id.product_model_id']]['schedule_offset'];

                    for($i = 0; $i < $nb_nights; ++$i) {
                        $c_date = mktime(0, 0, 0, $month, $day+$i+$offset, $year);
                        $c_schedule_from = $schedule_from;
                        $c_schedule_to   = $schedule_to;

                        // if sojourn span over several days
                        if($nb_nights > 1) {
                            $c_schedule_to = 24 * 3600;       // midnight next day
                            if($i > 1) {
                                $c_schedule_from = 0;         // midnight same day
                            }
                            if($i == $nb_nights-1) {          // last day
                                $c_schedule_to = $schedule_to;
                            }
                        }

                        for($j = 0; $j < $qty; ++$j) {
                            $consumption = [
                                'booking_id'            => $line['booking_id'],
                                'booking_line_group_id' => $line['booking_line_group_id'],
                                'booking_line_id'       => $lid,
                                'date'                  => $c_date,
                                'schedule_from'         => $c_schedule_from,
                                'schedule_to'           => $c_schedule_to,
                                'product_id'            => $line['product_id'],
                                'is_rental_unit'        => $is_rental_unit,
                                'rental_unit_id'        => $rental_unit_id
                            ];

                            $cid = $om->create('sale\booking\Consumption', $consumption, $lang);
                            if($cid > 0) {
                                $consumptions_ids[] = $cid;
                            }
                        }
                    }
                    $om->write(get_called_class(), $lid, ['consumptions_ids' => [$consumptions_ids] ]);
                }
            }


        }

    }


}