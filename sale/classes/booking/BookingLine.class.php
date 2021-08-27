<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingLine extends Model {

    public static function getName() {
        return "Booking line";
    }

    public static function getDescription() {
        return "Booking lines describe the products and quantities that are part of a booking.";
    }

    public static function getColumns() {
        return [

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their booking).',
                'ondelete'          => 'cascade',        // delete line when parent group is deleted
                'required'          => true              // must be set at creation
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'onchange'          => 'sale\booking\BookingLine::onchangeProductId'
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the line relates to.'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Price adapters holding the manual discounts applied on the line.'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product items for the line.',
                'default'           => 1.0
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Quantity of free items (granted as a gift).',
                'default'           => 0
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the line have to be sorted when presented visually.',
                'default'           => 1
            ],

            'payment_mode' => [
                'type'              => 'string',
                'selection'         => ['invoice', 'cash', 'free'],
                'default'           => 'invoice',
                'description'       => 'The way the line is intended to be paid.',
            ],

            'is_paid' => [
                'type'              => 'boolean',
                'description'       => 'Has the line been paid already?',
                'default'           => false
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final (computed) price.',
                'function'          => 'sale\booking\BookingLine::getPrice',
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'VAT rate that applies to this line.',
                'function'          => 'sale\booking\BookingLine::getVatRate'
            ]
        ];
    }

    /**
     * Update the price_id according to booking line settings.
     */
    public static function onchangeProductId($om, $oids, $lang) {
        self::_updatePriceId($om, $oids, $lang);
    }

// #todo - _updatePriceId should be called upon center_id change

    public static function _updatePriceId($om, $oids, $lang) {
        $lines = $om->read(__CLASS__, $oids, ['booking_line_group_id.date_from', 'product_id', 'booking_id.center_id.price_list_category_id']);

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
                $price_list_id = array_shift(array_keys($price_lists));
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
                    $om->write(__CLASS__, $line_id, ['price_id' => $prices_ids[0]]);
                }
                else {
                    $om->write(__CLASS__, $line_id, ['price_id' => null]);
                    trigger_error("QN_DEBUG_ORM::no matching price found for product {$line['product_id']} in price_list $price_list_id", QN_REPORT_ERROR);
                }
            }
            else {
                $om->write(__CLASS__, $line_id, ['price_id' => null]);
                $date = date('Y-m-d', $line['booking_line_group_id.date_from']);
                trigger_error("QN_DEBUG_ORM::no matching price list found for date {$date}", QN_REPORT_ERROR);
            }
        }
    }

    /**
     * Compute the final total price of the line
     */
    public static function getPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.price', 'price_adapters_ids']);
        foreach($lines as $oid => $odata) {
            $price = (float) $odata['price_id.price'];
            $disc_percent = 0.0;
            $disc_value = 0.0;
            $adapters = $om->read('sale\booking\BookingLinePriceAdapter', $odata['price_adapters_ids'], ['is_manual_discount', 'type', 'value', 'discount_id']);
            foreach($adapters as $aid => $adata) {
                if($adata['is_manual_discount'] == true) {
                    if($adata['type'] == 'amount') {
                        $disc_value += $adata['value'];
                    }
                    else if($adata['type'] == 'percent') {
                        $disc_percent += $adata['value'];
                    }
                }
            }
            $result[$oid] = ($price * (1-$disc_percent)) - $disc_value;
        }
        return $result;
    }

    public static function getVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.accounting_rule_id.vat_rule.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = $odata['price_id.accounting_rule_id.vat_rule.rate'];
        }
        return $result;
    }

    public static function getConstraints() {
        return [
            'qty' =>  [
                'lte_zero' => [
                    'message'       => 'Quantity must be a positive value.',
                    'function'      => function ($qty, $values) {
                        return ($qty <= 0);
                    }
                ]
            ]

        ];
    }
}