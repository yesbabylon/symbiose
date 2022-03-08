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

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the pack relates to.',
                'visible'           => ['has_pack', '=', true],
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangePriceId'
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'VAT rate that applies to this group, when relating to a pack_id.',
                'function'          => 'lodging\sale\booking\BookingLineGroup::getVatRate',
                'store'             => true,
                'visible'           => ['has_pack', '=', true],
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded unit price (with automated discounts applied).',
                'function'          => 'lodging\sale\booking\BookingLineGroup::getUnitPrice',
                'store'             => true,
                'visible'           => ['has_pack', '=', true]
            ],

            'is_autosale' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relate to autosale products?',
                'default'           => false
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false,
                'visible'           => ['has_pack', '=', true],
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeIsLocked'
            ],

            'qty' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Quantity of product items for the group (pack).',
                'function'          => 'lodging\sale\booking\BookingLineGroup::getQty',
                'visible'           => ['has_pack', '=', true]
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Day of arrival.",
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeDateFrom',
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Day of departure.",
                'default'           => time(),
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeDateTo'
            ],

            'sojourn_type' => [
                'type'              => 'string',
                'selection'         => ['GA', 'GG'],
                'default'           => 'GG',
                'description'       => 'The kind of sojourn the group is about.',
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeSojournType'
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1,
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeNbPers'
            ],

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group.",
                'required'          => true,
                'onchange'          => 'lodging\sale\booking\BookingLineGroup::onchangeRateClassId'
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
                'required'          => true,
                'ondelete'          => 'cascade'         // delete group when parent booking is deleted
            ],

            'accomodations_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines relating to accomodations.',
                'ondetach'          => 'delete',
                'domain'            => ['is_accomodation', '=', true]
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'lodging\sale\booking\BookingLineGroup::getPrice'
                // #todo - set store to true
            ]

        ];
    }

    public static function getVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
        }
        return $result;
    }

    public static function getQty($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['has_pack', 'is_locked', 'pack_id.product_model_id.qty_accounting_method', 'nb_pers', 'nb_nights']);
        foreach($groups as $gid => $group) {
            $result[$gid] = 1;
            if($group['has_pack'] && $group['is_locked']) {
                // apply quantity (either nb_pers or nb_nights) and price adapters
                if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                    $qty = $group['nb_nights'];
                }
                else if($group['pack_id.product_model_id.qty_accounting_method'] == 'person') {
                    $qty = $group['nb_pers'] * $group['nb_nights'];
                }
                else {
                    $qty = $group['nb_pers'];
                }
                $result[$gid] = floatval($qty);
            }
        }
        return $result;
    }

    /**
     * Compute the VAT excl. unit price of the group, with automated discounts applied.
     *
     */
    public static function getUnitPrice($om, $oids, $lang) {
        $result = [];

        $groups = $om->read(__CLASS__, $oids, ['price_id.price']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {

                $price_adapters_ids = $om->search('lodging\sale\booking\BookingPriceAdapter', [
                    ['booking_line_group_id', '=', $gid],
                    ['booking_line_id','=', 0],
                    ['is_manual_discount', '=', false]
                ]);

                $disc_value = 0.0;
                $disc_percent = 0.0;

                if($price_adapters_ids > 0 && count($price_adapters_ids)) {
                    $adapters = $om->read('lodging\sale\booking\BookingPriceAdapter', $price_adapters_ids, ['type', 'value', 'discount_id.discount_list_id.rate_max']);

                    foreach($adapters as $aid => $adata) {
                        if($adata['type'] == 'amount') {
                            $disc_value += $adata['value'];
                        }
                        else if($adata['type'] == 'percent') {
                            if($adata['discount_id.discount_list_id.rate_max'] && ($disc_percent + $adata['value']) > $adata['discount_id.discount_list_id.rate_max']) {
                                $disc_percent = $adata['discount_id.discount_list_id.rate_max'];
                            }
                            else {
                                $disc_percent += $adata['value'];
                            }
                        }
                    }
                }

                $result[$gid] = round(($group['price_id.price'] * (1-$disc_percent)) - $disc_value, 2);
            }
        }
        return $result;
    }

    /**
     * Compute the VAT incl. total price of the group (pack), with manual and automated discounts applied.
     *
     */
    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['booking_lines_ids', 'is_locked', 'has_pack', 'unit_price', 'vat_rate', 'qty']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;

                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['has_pack'] && $group['is_locked']) {
                    $result[$gid] = round($group['unit_price'] * $group['qty'] * (1 + $group['vat_rate']), 2);
                }
                // otherwise, price is the sum of bookingLines
                else {
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['price']);
                    if($lines > 0 && count($lines)) {
                        foreach($lines as $line) {
                            $result[$gid] += $line['price'];
                        }
                        $result[$gid] = round($result[$gid], 2);
                    }
                }
            }
        }
        return $result;
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
                $om->write(__CLASS__, $update_groups_ids, ['vat_rate' => null, 'unit_price' => null ]);
                // #todo - reset booking total price (if price set to store)
            }
        }
    }

    public static function onchangePriceId($om, $oids, $lang) {
        $om->write(get_called_class(), $oids, ['vat_rate' => null,'unit_price' => null]);
    }

    /**
     * Update is_locked field according to selected pack (pack_id).
     * (This is done when pack_id is changed, but can be manually set by the user afterward.)
     *
     * Since this method is called, we assume that current group has 'has_pack' set to true,
     * and that pack_id relates to a product_model that is a pack.
     */
    public static function onchangePackId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangePackId", QN_REPORT_DEBUG);

        self::_updatePackId($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, [
            'date_from',
            'nb_pers',
            'is_locked',
            'booking_lines_ids',
            'pack_id.product_model_id.qty_accounting_method',
            'pack_id.product_model_id.has_duration',
            'pack_id.product_model_id.duration',
            'pack_id.product_model_id.capacity'
        ]);

        foreach($groups as $gid => $group) {
            $updated_fields = ['vat_rate' => null];

            // if targeted product model has its own duration, date_to is updated accordingly
            if($group['pack_id.product_model_id.has_duration']) {
                $updated_fields['date_to'] = $group['date_from'] + ($group['pack_id.product_model_id.duration'] * 60*60*24);
                // will update price_adapters, nb_nights
            }

            if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                $updated_fields['nb_pers'] = $group['pack_id.product_model_id.capacity'];
                // will update price_adapters
            }
            else {
                // always update nb_pers
                // to make sure to triggered self::_updatePriceAdapters and BookingLine::_updatePack
                $updated_fields['nb_pers'] = $group['nb_pers'];
            }

            $om->write(__CLASS__, $gid, $updated_fields, $lang);

            // notify bookinglines that pack_id has been updated
            // BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang); 

        }
    }

    public static function onchangeDateFrom($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateFrom", QN_REPORT_DEBUG);

        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'has_pack', 'nb_nights', 'booking_lines_ids']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // force parent booking to recompute date_from
                $om->write('lodging\sale\booking\Booking', $group['booking_id'], ['date_from' => null]);
                // notify booking lines that price_id has to be updated
                BookingLine::_updatePriceId($om, $group['booking_lines_ids'], $lang);
                // notify bookinglines that pack_id has been updated
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }
                else {
                    // if lines do not belong to a pack, update qty and price if their product has qty_accounting_method set to 'person'
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['product_id.product_model_id.qty_accounting_method']);
                    foreach($lines as $lid => $line) {
                        if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                            $om->write('lodging\sale\booking\BookingLine', $lid, ['qty' => $group['nb_nights']]);
                        }
                        else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                            $om->write('lodging\sale\booking\BookingLine', $lid, ['qty' => $group['nb_pers'] * $group['nb_nights']]);
                        }
                    }
                }
            }
        }
    }

    public static function onchangeDateTo($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeDateTo", QN_REPORT_DEBUG);

        $om->write(__CLASS__, $oids, ['nb_nights' => null ]);
        self::_updatePriceAdapters($om, $oids, $lang);

        $groups = $om->read(__CLASS__, $oids, ['booking_id', 'has_pack', 'nb_nights', 'nb_pers', 'booking_lines_ids']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // force parent booking to recompute date_to
                $om->write('lodging\sale\booking\Booking', $group['booking_id'], ['date_to' => null]);
                // notify bookinglines that dates has been updated (update )
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }
                else {
                    // if lines do not belong to a pack, update qty and price if their product has qty_accounting_method set to 'person'
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['product_id.product_model_id.qty_accounting_method']);
                    foreach($lines as $lid => $line) {
                        if($line['product_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                            $om->write('lodging\sale\booking\BookingLine', $lid, ['qty' => $group['nb_nights']]);
                        }
                        else if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                            $om->write('lodging\sale\booking\BookingLine', $lid, ['qty' => $group['nb_pers'] * $group['nb_nights']]);
                        }
                    }
                }
            }
        }
    }

    public static function onchangeRateClassId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeRateClassId", QN_REPORT_DEBUG);

        self::_updatePriceAdapters($om, $oids, $lang);
        $om->write(__CLASS__, $oids, ['unit_price' => null]);
    }

    public static function onchangeSojournType($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeSojournType", QN_REPORT_DEBUG);
        self::_updatePriceAdapters($om, $oids, $lang);
        $om->write(__CLASS__, $oids, ['unit_price' => null]);        
    }

    public static function onchangeNbPers($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BookingLineGroup:onchangeNbPers", QN_REPORT_DEBUG);

        self::_updatePriceAdapters($om, $oids, $lang); 

        $groups = $om->read(__CLASS__, $oids, ['nb_nights', 'nb_pers', 'has_pack', 'is_locked', 'booking_lines_ids']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $group) {
                // notify bookinglines that pack_id has been updated (qty will be updated)
                if($group['has_pack']) {
                    BookingLine::_updatePack($om, $group['booking_lines_ids'], $lang);
                }
                else {
                    // if lines do not belong to a pack, update qty and price if their product has qty_accounting_method set to 'person'
                    $lines = $om->read('lodging\sale\booking\BookingLine', $group['booking_lines_ids'], ['product_id.product_model_id.qty_accounting_method']);
                    foreach($lines as $lid => $line) {
                        if($line['product_id.product_model_id.qty_accounting_method'] == 'person') {
                            $om->write('lodging\sale\booking\BookingLine', $lid, ['qty' => $group['nb_pers'] * max(1, $group['nb_nights'])]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Create Price adapters according to group settings.
     *
     * Price adapters are applied only on meal and accomodation products
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

        $line_groups = $om->read(__CLASS__, $oids, ['rate_class_id', 'sojourn_type', 'date_from', 'date_to', 'nb_pers', 'nb_nights', 'booking_id', 'is_locked',
                                                    'booking_lines_ids', 
                                                    'booking_id.customer_id.count_booking_24',
                                                    'booking_id.center_id.season_category_id',
                                                    'booking_id.center_id.discount_list_category_id']);

        foreach($line_groups as $group_id => $group) {
            /*
                Find the first Discount List that matches the booking dates
            */
            
            // the discount list category to use is the one defined for the center, unless it is ('GA' or 'GG') AND sojourn_type <> category.name
            $discount_category_id = $group['booking_id.center_id.discount_list_category_id'];

            if(in_array($discount_category_id, [1 /*GA*/, 2 /*GG*/])) {
                if($discount_category_id == 1 && $group['sojourn_type'] != 'GA') {
                    // force GG
                    $discount_category_id = 2;
                }
                else if($discount_category_id == 2 && $group['sojourn_type'] != 'GG') {
                    // force GA
                    $discount_category_id = 1;
                }
            }

            $discount_lists_ids = $om->search('sale\discount\DiscountList', [
                ['rate_class_id', '=', $group['rate_class_id']],
                ['discount_list_category_id', '=', $discount_category_id],
                ['valid_from', '<=', $group['date_from']],
                ['valid_until', '>=', $group['date_from']]
            ]);

            $discount_lists = $om->read('sale\discount\DiscountList', $discount_lists_ids, ['id', 'discounts_ids', 'rate_min']);
            $discount_list_id = 0;
            $discount_list = null;
            if($discount_lists > 0 && count($discount_lists)) {
                // use first match (there should alwasy be only one or zero)
                $discount_list = array_pop($discount_lists);
                $discount_list_id = $discount_list['id'];
                trigger_error("QN_DEBUG_ORM:: match with discount List {$discount_list_id}", QN_REPORT_DEBUG);
            }
            else {
                trigger_error("QN_DEBUG_ORM:: no discount List found", QN_REPORT_DEBUG);
            }
            /*
                Search for matching Discounts within the found Discount List
            */
            if($discount_list_id) {
                $operands = [];
                $operands['count_booking_24'] = $group['booking_id.customer_id.count_booking_24'];
                $operands['duration'] = ($group['date_to']-$group['date_from'])/(60*60*24);     // duration in nights
                $operands['nb_pers'] = $group['nb_pers'];                                       // number of participants

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

                $discounts = $om->read('sale\discount\Discount', $discount_list['discounts_ids'], ['value', 'type', 'conditions_ids']);

                // filter discounts based on related conditions
                $discounts_to_apply = [];
                // keep track of the final rate (for discounts with type 'percent')
                $rate_to_apply = 0;

                // filter discounts to be applied on booking lines
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
                        if(!$valid) break;
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled", QN_REPORT_DEBUG);
                        $discounts_to_apply[$discount_id] = $discount;
                        if($discount['type'] == 'percent') {
                            $rate_to_apply += $discount['value'];
                        }
                    }
                }

                // if guaranteed rate (rate_min) has not been reached, add a discount with rate_min
                if($rate_to_apply < $discount_list['rate_min'] ) {
                    // remove all 'percent' discounts
                    foreach($discounts_to_apply as $discount_id => $discount) {
                        if($discount['type'] == 'percent') {
                            unset($discounts_to_apply[$discount_id]);
                        }
                    }
                    // add a custom discount with minimal rate
                    $discounts_to_apply[0] = [
                        'type'      => 'percent',
                        'value'     => $discount_list['rate_min']
                    ];
                }

                // apply all applicable discounts
                foreach($discounts_to_apply as $discount_id => $discount) {

                    /*
                        create price adapter for group only, according to discount and group settings
                        (needed in case group targets a pack with own price)
                    */
                    $price_adapters_ids = $om->create('lodging\sale\booking\BookingPriceAdapter', [
                        'is_manual_discount'    => false,
                        'booking_id'            => $group['booking_id'],
                        'booking_line_group_id' => $group_id,
                        'booking_line_id'       => 0,
                        'discount_id'           => $discount_id,
                        'discount_list_id'      => $discount_list_id,
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
                        'is_meal',
                        'is_accomodation',
                        'qty_accounting_method'
                    ]);

                    foreach($lines as $line_id => $line) {
                        // do not apply discount on lines that cannot have a price
                        if($group['is_locked']) continue;
                        // do not apply freebies on accomodations for groups
                        if($discount['type'] == 'freebie' && $line['qty_accounting_method'] == 'accomodation') continue;                        
                        if( 
                            // for GG: apply discounts only on accomodations
                            (
                                $group['sojourn_type'] == 'GG'
                                &&
                                $line['is_accomodation']
                            )
                            ||
                            // for GA: apply discounts on meals and accomodations                            
                            (
                                $group['sojourn_type'] == 'GA'
                                &&
                                (
                                    $line['is_accomodation']
                                    ||
                                    $line['is_meal']
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
                                'discount_list_id'      => $discount_list_id,
                                'type'                  => $discount['type'],
                                'value'                 => ($discount['type'] == 'freebie')?($discount['value']*$group['nb_nights']):$discount['value']
                            ]);
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

        $groups = $om->read(__CLASS__, $oids, [
            'booking_id', 'booking_lines_ids',
            'pack_id.is_locked',
            'pack_id.pack_lines_ids',
            'pack_id.product_model_id.has_own_price'
        ]);

        foreach($groups as $gid => $group) {

            /*
                Update current group according to selected pack
            */

            // might need to update price_id
            if($group['pack_id.product_model_id.has_own_price']) {
                $om->write(__CLASS__, $gid, ['is_locked' => true], $lang);
            }
            else {
                $om->write(__CLASS__, $gid, ['is_locked' => $group['pack_id.is_locked'] ], $lang);
            }

            /*
                Reset booking_lines (updating booking_lines_ids will trigger ondetach event)
            */
            $om->write(__CLASS__, $gid, ['booking_lines_ids' => array_map(function($a) { return "-$a";}, $group['booking_lines_ids'])]);

            /*
                Create booking lines according to pack composition
            */
            $pack_lines = $om->read('lodging\sale\catalog\PackLine', $group['pack_id.pack_lines_ids'], [
                'child_product_id', 'has_own_qty', 'own_qty', 'child_product_id.product_model_id.qty_accounting_method'
            ]);
            $order = 1;

            foreach($pack_lines as $pid => $pack_line) {
                $line = [
                    'order'                     => $order,
                    'booking_id'                => $group['booking_id'],
                    'booking_line_group_id'     => $gid,
                    'product_id'                => $pack_line['child_product_id'],
                    'qty_accounting_method'     => $pack_line['child_product_id.product_model_id.qty_accounting_method']
                ];
                if($pack_line['has_own_qty']) {
                    $line['has_own_qty'] = true;
                    $line['qty'] = $pack_line['own_qty'];
                }
                $lid = $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                if($lid > 0) {
                    $om->write(__CLASS__, $gid, ['booking_lines_ids' => ["+$lid"] ]);
                }
                ++$order;
            }
        }

        //#memo - consumptions are updated by the bookingLines
    }


    /**
     * Find and set price according to group settings.
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
                Find the Price List that matches the criteria from the booking with the shortest duration
            */
            $price_lists_ids = $om->search(
                'sale\price\PriceList',
                [
                    ['price_list_category_id', '=', $group['booking_id.center_id.price_list_category_id']],
                    ['is_active', '=', true]
                ],
                ['duration' => 'asc']
            );

            $found = false;
            if($price_lists_ids > 0 && count($price_lists_ids)) {
                /*
                    Search for a matching Price within the found Price Lists
                */
                foreach($price_lists_ids as $price_list_id) {
                    // there should be exactly one matching price
                    $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $group['pack_id']] ]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        /*
                            Assign found Price to current group
                        */
                        $found = true;
                        $om->write(__CLASS__, $gid, ['price_id' => $prices_ids[0]]);
                        break;
                    }
                }
            }
            if(!$found) {
                $om->write(__CLASS__, $gid, ['price_id' => null, 'vat_rate' => 0, 'unit_price' => 0]);
                $date = date('Y-m-d', $group['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for group at date {$date}", QN_REPORT_ERROR);
            }
        }
    }
}