<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingLineGroup extends Model {

    public static function getName() {
        return "Booking line group";
    }

    public static function getDescription() {
        return "Booking line groups are related to a booking and describe one or more sojourns and their related consumptions.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Mnemo for the group.',
                'default'           => ''
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order of the group in the list.',
                'default'           => 1
            ],

            'has_pack' => [
                'type'              => 'boolean',
                'description'       => 'Does the group relates to a pack?',
                'default'           => false
            ],

            'pack_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\Product',
                'description'       => 'Pack (product) the group relates to, if any.',
                'visible'           => ['has_pack', '=', true]
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the pack relates to.',
                'visible'           => ['has_pack', '=', true]
            ],

            'is_locked' => [
                'type'              => 'boolean',
                'description'       => 'Are modifications disabled for the group?',
                'default'           => false
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "Day of arrival.",
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "Day of departure.",
                'default'           => time()
            ],

            'nb_nights' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Amount of nights of the sojourn.',
                'function'          => 'sale\booking\BookingLineGroup::getNbNights',
                'store'             => true
            ],

            'nb_pers' => [
                'type'              => 'integer',
                'description'       => 'Amount of persons this group is about.',
                'default'           => 1
            ],

            /* a booking can be split into several groups on which distinct rate classes apply, by default the rate_class of the customer is used */
            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "The rate class that applies to the group.",
                'required'          => true
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Booking lines that belong to the group.',
                'ondetach'          => 'delete'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Consumptions related to the group.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => 'Price adapters that apply to all lines of the group (based on group settings).'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'        // delete group when parent booking is deleted
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final (computed) price for all lines.',
                'function'          => 'sale\booking\BookingLineGroup::getPrice'
// #todo - set store to true
            ]

        ];
    }

    public static function getNbNights($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['date_from', 'date_to']);
        foreach($groups as $gid => $group) {
            $result[$gid] = floor( ($group['date_to'] - $group['date_from']) / (60*60*24) );
        }
        return $result;
    }

    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $groups = $om->read(__CLASS__, $oids, ['booking_lines_ids', 'is_locked', 'has_pack', 'price_id', 'pack_id', 'nb_pers', 'nb_nights']);

        if($groups > 0 && count($groups)) {
            foreach($groups as $gid => $group) {
                $result[$gid] = 0.0;
                $has_own_price = false;
                // if the group relates to a pack and the product_model targeted by the pack has its own Price, then this is the one to return
                if($group['has_pack'] && $group['is_locked']) {
                    $res = $om->read(__CLASS__, $gid, [
                        'pack_id.product_model_id.has_own_price', 
                        'pack_id.product_model_id.qty_accounting_method', 
                        'price_id.price', 
                        'price_id.accounting_rule_id.vat_rule_id.rate'
                    ]);
                    if($res > 0 && count($res)) {
                        if($res[$gid]['pack_id.product_model_id.has_own_price']) {
                            $group = array_merge($group, $res[$gid]);
                            $has_own_price = true;
                        }
                    }
                }
                if($has_own_price) {
                    // #todo - add support for qty_accounting_method by night (memo - product_model.qty_accounting_method is defined in lodging package)
                    $price_adapters_ids = $om->search('sale\booking\BookingPriceAdapter', [ ['booking_line_group_id', '=', $gid], ['booking_line_id','=', 0] ]);
                    $adapters = $om->read('sale\booking\BookingPriceAdapter', $price_adapters_ids, ['type', 'value', 'discount_id.discount_list_id.rate_max']);

                    $disc_value = 0.0;
                    $disc_percent = 0.0;

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
 
                    // apply quantity (either nb_pers or nb_nights) and price adapters
                    if($group['pack_id.product_model_id.qty_accounting_method'] == 'accomodation') {
                        $qty = $group['nb_nights'];
                    }
                    else {
                        $qty = $group['nb_pers'];
                    }                    
                    $price = $group['price_id.price'] * (1-$disc_percent) - $disc_value;
                    $result[$gid] = round($price * $qty * (1 + $group['price_id.accounting_rule_id.vat_rule_id.rate']), 2);
                }
                // otherwise, price is the sum of bookingLines
                else {
                    $lines = $om->read('sale\booking\BookingLine', $group['booking_lines_ids'], ['price']);
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

    







   

    
}