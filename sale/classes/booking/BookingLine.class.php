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
                'required'          => true
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
                'required'          => true
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the line relates to.',
                'required'          => true
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Quantity of product items for the line.',
                'required'          => true
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

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLinePriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'Price adapters that apply to the line.'
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
                'function'          => 'sale\booking\BookingLine::getVat',
            ]            
        ];
    }

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

    public static function getVat($om, $oids, $lang) {
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
                    'message'       => 'Quantity must be a positive integer.',
                    'function'      => function ($qty, $values) {
                        return ($qty <= 0);
                    }    
                ]
            ]

        ];
    }    
}