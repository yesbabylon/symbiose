<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class BookingLineGroup extends \sale\booking\BookingLineGroup {

    public static function getName() {
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {
        return [

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true],
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangePackId'
            ],

            'date_from' => [
                'type'              => 'datetime',
                'description'       => "Time of arrival.",
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeDateFrom',
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'datetime',
                'description'       => "Time of departure.",
                'default'           => time()
            ],

            'sojourn_type' => [
                'type'              => 'string',
                'selection'         => ['GA', 'GG'],
                'default'           => 'GG',
                'description'       => 'The kind of sojourn the group is about.',
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1,
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeNbPers',
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Price adapters that apply to all lines of the group (based on group settings).'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true
            ]

        ];
    }

    /**
     * Update is_locked field according to selected pack (pack_id).
     * This is done when pack_id is changed, but can be manually set by the user afterward.
     */
    public static function onchangePackId($om, $oids, $lang) {
        $groups = $om->read(__CLASS__, $oids, [
            'booking_id', 'booking_lines_ids', 'nb_pers', 'nb_nights',
            'pack_id.is_locked',
            'pack_id.product_model_id.pack_lines_ids', 'pack_id.product_model_id.has_own_price'
        ]);

        $groups_update_price_id = [];

        foreach($groups as $gid => $group) {

            /*
                Update is_locked field
            */
            // if targeted product model has its own price, set price_id accordingly
            if($group['pack_id.product_model_id.has_own_price']) {
                $om->write(__CLASS__, $gid, ['is_locked' => true], $lang);
                $groups_update_price_id[] = $gid;
            }
            else {
                $om->write(__CLASS__, $gid, ['is_locked' => $group['pack_id.is_locked'] ], $lang);
            }

            /*
                Reset booking_lines
            */            
            $om->write(__CLASS__, $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $group['booking_lines_ids'])]);

            /*
                Create booking lines according to pack composition
            */
            $pack_lines_ids = $group['pack_id.product_model_id.pack_lines_ids'];
            $pack_lines = $om->read('sale\catalog\PackLine', $pack_lines_ids, ['child_product_id']);
            $products_ids = array_map( function ($a) {return $a['child_product_id'];}, $pack_lines);
            $products = $om->read('lodging\sale\catalog\Product', $products_ids, ['product_model_id.qty_accounting_method']);
            $order = 1;
            foreach($products as $pid => $product) {
                $line = [
                    'order'                     => $order,
                    'booking_id'                => $group['booking_id'],
                    'booking_line_group_id'     => $gid,
                    'product_id'                => $pid
                ];
                if($product['product_model_id.qty_accounting_method'] == 'person') {
                    $line['qty'] = $group['nb_pers'];
                }
                else {
                    $line['qty'] = $group['nb_nights'];
                }
                $lid = $om->create('lodging\sale\booking\BookingLine', $line);
                if($lid > 0) {
                    $om->write(__CLASS__, $gid, ['booking_lines_ids' => ["+$lid"] ]);
                }
                ++$order;
            }
        }

        /*
            Update price for groups having a pack with own price
        */
        self::_updatePriceId($om, $groups_update_price_id, $lang);

        /*
            Update price adapters
        */
        self::_updatePriceAdapters($om, $oids, $lang);

        //#memo - consumptions are updated by the bookingLines
    }



    public static function onchangeRateClassId($om, $oids, $lang) {
        self::_updatePriceAdapters($om, $oids, $lang);
    }

    public static function onchangeDateFrom($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);
        $booking_lines_ids = $om->read(__CLASS__, $oids, ['booking_lines_ids']);
        if($booking_lines_ids > 0 && count($booking_lines_ids)) {
            BookingLine::_updatePriceId($om, $booking_lines_ids, $lang);
        }
    }

    public static function onchangeDateTo($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);
    }

    public static function onchangeNbPers($om, $oids, $lang) {
        self::_updatePriceAdapters($om, $oids, $lang);
    }

    /**
     * Create Price adapters according to group settings.
     *
     * create priceAdapters only for meal and accomodation products
     *  
     * (This method is called upon booking_id.customer_id change)
     */
    public static function _updatePriceAdapters($om, $oids, $lang) {
        /*
            Remove all previous price adapters that were automatically created
        */
        $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [ ['booking_line_group_id', 'in', $oids], ['is_manual_discount','=', false]]);
        $om->remove('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, true);

        $line_groups = $om->read(__CLASS__, $oids, ['rate_class_id', 'date_from', 'date_to', 'nb_pers', 'booking_id', 'is_locked',
                                                    'booking_lines_ids', 'sojourn_type',
                                                    'booking_id.customer_id.count_booking_24',
                                                    'booking_id.center_id.season_category_id',
                                                    'booking_id.center_id.discount_list_category_id']);

        foreach($line_groups as $group_id => $group) {
            /*
                Find the first Discount List that matches the booking dates
            */
            $discount_lists_ids = $om->search('sale\discount\DiscountList', [
                ['rate_class_id', '=', $group['rate_class_id']],
                ['discount_list_category_id', '=', $group['booking_id.center_id.discount_list_category_id']],
                ['valid_from', '<=', $group['date_from']],
                ['valid_until', '>=', $group['date_from']]
            ]);

            $discount_lists = $om->read('sale\discount\DiscountList', $discount_lists_ids, ['id', 'discounts_ids']);
            $discount_list_id = 0;
            if($discount_lists > 0 && count($discount_lists)) {
                $discount_list_id = array_keys($discount_lists)[0];
            }
            /*
                Search for matching Discounts within the found Discount List
            */
            if($discount_list_id) {
                $operands = [];
                $operands['count_booking_24'] = $group['booking_id.customer_id.count_booking_24'];
                $operands['duration'] = ($group['date_to']-$group['date_from'])/(60*60*24);     // duration in nights
                $operands['nb_pers'] = $group['nb_pers'];                                       // number of participants

                $season_category = $group['booking_id.center_id.season_category_id'];
                $date = $group['date_from'];
                /*
                    Pick up the first season period that matches the year and the season category of the center
                */
                $year = date('Y', $date);
                $seasons_ids = $om->search('sale\season\SeasonPeriod', [
                    ['season_category_id', '=', $group['booking_id.center_id.season_category_id']],
                    ['date_from', '<=', $group['date_from']],
                    ['date_to', '>=', $group['date_from']],
                    ['year', '=', $year]
                ]);

                $periods = $om->read('sale\season\SeasonPeriod', $seasons_ids, ['id', 'season_type_id.name']);
                if($periods > 0 && count($periods)){
                    $period = array_shift($periods);
                    $operands['season'] = $period['season_type_id.name'];
                }

                $discounts_ids = $om->search('sale\discount\DiscountList', [
                    ['discount_list_category_id', '=', $group['booking_id.center_id.discount_list_category_id']],
                    ['valid_from', '<=', $group['date_from']],
                    ['valid_until', '>=', $group['date_from']]
                ]);

                $discounts = $om->read('sale\discount\Discount', $discount_lists[$discount_list_id]['discounts_ids'], ['value', 'type', 'conditions_ids']);

                foreach($discounts as $discount_id => $discount) {
                    $conditions = $om->read('sale\discount\Condition', $discount['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!in_array($condition['operator'], ['>', '>=', '<', '<=', '='])) {
                            // unknown operator
                            continue;
                        }
                        $operator = $condition['operator'];
                        if($operator == '=') {
                            $operator = '==';
                        }
                        if(!isset($operands[$condition['operand']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operand']];
                        $value = $condition['value'];
                        if(!is_numeric($operand)) {
                            $operand = "'$operand'";
                        }
                        if(!is_numeric($value)) {
                            $value = "'$value'";
                        }
                        trigger_error(" testing {$operand} {$operator} {$value}", QN_REPORT_DEBUG);
                        $valid = $valid && (bool) eval("return ( {$operand} {$operator} {$value});");
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled", QN_REPORT_DEBUG);
                        /* 
                            create price adapter for group only, according to discount and group settings
                            (needed in case of pack with own price)
                        */
                        $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                            'is_manual_discount'    => false,
                            'booking_id'            => $group['booking_id'],
                            'booking_line_group_id' => $group_id,                            
                            'discount_id'           => $discount_id,
                            'type'                  => $discount['type'],
                            'value'                 => $discount['value']
                        ]);

                        /*
                            create related price adapter for all lines, according to discount and group settings
                        */
                        // read all lines from group
                        $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], [
                            'product_id',
                            'product_id.product_model_id',
                            'product_id.product_model_id.is_meal',
                            'product_id.product_model_id.is_accomodation'
                        ]);

                        foreach($lines as $line_id => $line) {
                            // do not apply discount on lines that cannot have a price 
                            if($group['is_locked']) continue;
                            if( (
                                    $group['sojourn_type'] == 'GG'
                                    &&
                                    $line['product_id.product_model_id.is_accomodation']
                                )
                                ||
                                (
                                    $group['sojourn_type'] == 'GA'
                                    &&
                                    (
                                        $line['product_id.product_model_id.is_accomodation']
                                        ||
                                        $line['product_id.product_model_id.is_meal']
                                    )
                                )
                            ) {
                                trigger_error("QN_DEBUG_ORM:: creating price adapter", QN_REPORT_DEBUG);
                                // current discount must be applied on the line: create a price adpter
                                $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                                    'is_manual_discount'    => false,
                                    'booking_id'            => $group['booking_id'],
                                    'booking_line_group_id' => $group_id,
                                    'booking_line_id'       => $line_id,
                                    'discount_id'           => $discount_id,
                                    'type'                  => $discount['type'],
                                    'value'                 => $discount['value']
                                ]);
                            }
                        }
                    }
                }
            }
            else {
                $date = date('Y-m-d', $group['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching discount list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }
}