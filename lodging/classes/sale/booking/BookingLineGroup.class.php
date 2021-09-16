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
            'has_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relates to a pack?',
                'default'           => false,
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeHasPack'
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true],
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangePackId'
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false,
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeIsLocked'
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
                'default'           => time(),
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeDateTo',                
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

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group.",
                'required'          => true,
                'onchange'          => 'sale\booking\BookingLineGroup::onchangeRateClassId'
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
            ],


            'accomodations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines related to accomodations.',
                'ondetach'          => 'delete',
                'domain'            => ['qty_accounting_method', '=', 'accomodation']
            ]


        ];
    }


    public static function onchangeHasPack($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeHasPack", QN_REPORT_DEBUG);

        $groups = $om->read(__CLASS__, $oids, ['has_pack']);
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                if(!$group['has_pack']) {
                    $om->write(__CLASS__, $oids, ['is_locked' => false, 'pack_id' => null ]);
                }
            }
        }
    }

    public static function onchangeIsLocked($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeIsLocked", QN_REPORT_DEBUG);

        $groups = $om->read(__CLASS__, $oids, ['is_locked']);
        $update_groups_ids = [];
        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                if($group['is_locked']) {
                    $update_groups_ids[] = $gid;
                }
            }
            if(count($update_groups_ids)) {
                self::_updatePriceId($om, $update_groups_ids, $lang);    
            }            
        }        
    }    

    /**
     * Update is_locked field according to selected pack (pack_id).
     * This is done when pack_id is changed, but can be manually set by the user afterward.
     * 
     * Since this method is called, we assume that current group has 'has_pack' set to ture, 
     * and that pack_id relates to a product_model that is a pack.
     */
    public static function onchangePackId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangePackId", QN_REPORT_DEBUG);
        self::_updatePackId($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, [
            'date_from',
            'booking_lines_ids',
            'pack_id.product_model_id.qty_accounting_method',
            'pack_id.product_model_id.has_duration',
            'pack_id.product_model_id.duration',
            'pack_id.product_model_id.capacity'            
        ]);
        
        foreach($groups as $gid => $group) {
            $updated_fields = [];

            // if targeted product model has its own duration, date_to is updated accordingly
            if($group['pack_id.product_model_id.has_duration']) {
                $updated_fields['date_to'] = $group['date_from'] + ($group['pack_id.product_model_id.duration'] * 60*60*24);
                // will update price_adapters, nb_nights                
            }
    
            if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                $updated_fields['nb_pers'] = $group['pack_id.product_model_id.capacity'];
                // will update price_adapters                
            }
    
            $om->write(__CLASS__, $gid, $updated_fields, $lang);
    
            // notify bookinglines that pack_id has been updated
            BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
        }
    }



    public static function onchangeRateClassId($om, $oids, $lang) {
        self::_updatePriceAdapters($om, $oids, $lang);
    }

    public static function onchangeDateFrom($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateFrom", QN_REPORT_DEBUG);
        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, ['has_pack', 'booking_lines_ids']);
        
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify booking lines that price_id has to be updated
                BookingLine::_updatePriceId($om, $group['booking_lines_ids'], $lang);
                // notify bookinglines that pack_id has been updated
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }                
            }
        }
    }

    public static function onchangeDateTo($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateTo", QN_REPORT_DEBUG);
        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, ['has_pack', 'booking_lines_ids']);
        
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify bookinglines that pack_id has been updated
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }                
            }
        }
    }

    public static function onchangeNbPers($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeNbPers", QN_REPORT_DEBUG);
        self::_updatePriceAdapters($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, ['has_pack', 'booking_lines_ids']);
        
        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify bookinglines that pack_id has been updated
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }                
            }
        }
    }

    /**
     * Create Price adapters according to group settings.
     *
     * create priceAdapters only for meal and accomodation products
     *  
     * (This method is called upon booking_id.customer_id change)
     */
    public static function _updatePriceAdapters($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:_updatePriceAdapters", QN_REPORT_DEBUG);
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
                            'booking_line_id'       => 0,
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


    /**
     * Update pack_id and re-create booking lines accordingly.
     * 
     */
    public static function _updatePackId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:_updatePackId", QN_REPORT_DEBUG);
        $groups = $om->read(get_called_class(), $oids, [
            'booking_id', 'booking_lines_ids',
            'pack_id.is_locked',
            'pack_id.product_model_id.pack_lines_ids',
            'pack_id.product_model_id.has_own_price'
        ]);

        foreach($groups as $gid => $group) {

            /*
                Update current group according to selected pack
            */

            // might need to update price_id
            if($group['pack_id.product_model_id.has_own_price']) {
                $om->write(get_called_class(), $gid, ['is_locked' => true], $lang);
            }
            else {
                $om->write(get_called_class(), $gid, ['is_locked' => $group['pack_id.is_locked'] ], $lang);
            }

            /*
                Reset booking_lines (updating booking_lines_ids will trigger ondetach event)
            */
            $om->write(get_called_class(), $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $group['booking_lines_ids'])]);

            /*
                Create booking lines according to pack composition
            */
            $pack_lines = $om->read('sale\catalog\PackLine', $group['pack_id.product_model_id.pack_lines_ids'], [
                'child_product_id', 'has_own_qty', 'own_qty', 'child_product_id.qty_accounting_method'
            ]);
            $order = 1;

            foreach($pack_lines as $pid => $pack_line) {
                $line = [
                    'order'                     => $order,
                    'booking_id'                => $group['booking_id'],
                    'booking_line_group_id'     => $gid,
                    'product_id'                => $pack_line['child_product_id'],
                    'qty_accounting_method'     => $pack_line['child_product_id.qty_accounting_method']
                ];
                if($pack_line['has_own_qty']) {
                    $line['has_own_qty'] = true;
                    $line['qty'] = $pack_line['own_qty'];
                }
                $lid = $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                if($lid > 0) {
                    $om->write(get_called_class(), $gid, ['booking_lines_ids' => ["+$lid"] ]);
                }
                ++$order;
            }
        }

        //#memo - consumptions are updated by the bookingLines
    }


/**
     * Find and set price list according to group settings.
     * This only applies when group targets a Pack with own price.
     *
     * Should only be called when is_locked == true
     * 
     * _updatePriceId is called upon change on: pack_id, is_locked, date_from, center_id
     */
    public static function _updatePriceId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\booking\BookingLineGroup:_updatePriceId", QN_REPORT_DEBUG);

        $groups = $om->read(__CLASS__, $oids, [
            'date_from',
            'pack_id',
            'booking_id.center_id.price_list_category_id'
        ]);

        foreach($groups as $gid => $group) {
            /*
                Find the first Price List that matches the criteria from the booking
            */
            $price_lists_ids = $om->search('sale\price\PriceList', [
                                                                       ['price_list_category_id', '=', $group['booking_id.center_id.price_list_category_id']],
                                                                       ['date_from', '<=', $group['date_from']],
                                                                       ['date_to', '>=', $group['date_from']]
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
                $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $group['pack_id']] ]);
                if($prices_ids > 0 && count($prices_ids)) {
                    /*
                        Assign found Price to current group
                    */
                    $om->write(__CLASS__, $gid, ['price_id' => $prices_ids[0]]);
                }
                else {
                    $om->write(__CLASS__, $gid, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0]);
                    trigger_error("QN_DEBUG_ORM::no matching price found for product {$group['product_id']} in price_list $price_list_id", QN_REPORT_ERROR);
                }
            }
            else {
                $om->write(__CLASS__, $gid, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0]);
                $date = date('Y-m-d', $group['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for date {$date}", QN_REPORT_ERROR);
            }
        }
    }    
}