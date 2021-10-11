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
                'description'       => 'The product (SKU) the line relates to.'
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price (retrieved by price list) the line relates to.',
                'onchange'          => 'sale\booking\BookingLine::onchangePriceId'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'booking_line_id',                
                'description'       => 'Consumptions related to the booking line.',
                'ondetach'          => 'delete'
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'description'       => 'All price adapters: auto and manual discounts applied on the line.',
                'onchange'          => 'sale\booking\BookingLine::onchangePriceAdaptersIds'
            ],

            'auto_discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'domain'            => ['is_manual_discount', '=', false],
                'description'       => 'Price adapters relating to auto discounts only.'
            ],

            'manual_discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'domain'            => ['is_manual_discount', '=', true],
                'description'       => 'Price adapters relating to manual discounts only.'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product items for the line.',
                'default'           => 1.0
            ],

            'has_own_qty' => [
                'type'              => 'boolean',
                'description'       => 'Set according to related pack line.',
                'default'           => false
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

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Unit price (with automated discounts applied).',
                'function'          => 'sale\booking\BookingLine::getUnitPrice',
                'store'             => true,
                'onchange'          => 'sale\booking\BookingLine::onchangeUnitPrice'
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'Final (computed) price.',
                'function'          => 'sale\booking\BookingLine::getTotalPrice',
                'store'             => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'VAT rate that applies to this line.',
                'function'          => 'sale\booking\BookingLine::getVatRate',
                'store'             => true,
                'onchange'          => 'sale\booking\BookingLine::onchangeVatRate'                
            ]
        ];
    }



    public static function onchangeUnitPrice($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['price' => null]);
    }

    public static function onchangeVatRate($om, $oids, $lang) {
        $om->write(__CLASS__, $oids, ['price' => null]);
    }

    public static function onchangePriceId($om, $oids, $lang) {
        // reset computed fields related to price
        $om->write(__CLASS__, $oids, ['unit_price' => null, 'price' => null, 'vat_rate' => null ]);
    }

    public static function onchangePriceAdaptersIds($om, $oids, $lang) {
        // reset computed fields related to price
        $om->write(__CLASS__, $oids, ['unit_price' => null, 'price' => null, 'vat_rate' => null ]);
    }


    /**
     * Update booking line quantities according to current pack (supposely after change occured).
     * 
     * pack_id refers to the parent booking_line_group_id.pack_id (there is no pack_id in BookingLine schema)
     * This method is called by BookingLineGroup::onchangePackId (and derived classes overloads)
     */
    public static function _updatePack($om, $oids, $lang) {
        // #todo                
    }

            
    /**
     * This method is called upon change on: qty
     */
    public static function _updateConsumptions($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\booking\BookingLine:_updateConsumptions", QN_REPORT_DEBUG);
        // #todo
    }

    /**
     * Compute the VAT excl. unit price of the line, according to manual and automated discounts.
     *
     */
    public static function getUnitPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, [
                    'price_id.price',
                    'auto_discounts_ids'
                ]);
        foreach($lines as $oid => $odata) {
            $price = (float) $odata['price_id.price'];
            $disc_percent = 0.0;
            $disc_value = 0.0;
            $adapters = $om->read('sale\booking\BookingPriceAdapter', $odata['auto_discounts_ids'], ['type', 'value', 'discount_id.discount_list_id.rate_max']);
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
            $result[$oid] = round(($price * (1-$disc_percent)) - $disc_value, 2);
        }
        return $result;
    }

    /**
     * Compute the VAT incl. total price of the line, according to manual and automated discounts.
     *
     */
    public static function getTotalPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, [
                    'qty',
                    'vat_rate',
                    'unit_price',
                    'auto_discounts_ids',
                    'manual_discounts_ids'
                ]);
// #todo we also need to set the limit according to related DiscountLists                
        foreach($lines as $oid => $odata) {
            $price = (float) $odata['unit_price'];
            $disc_percent = 0.0;
            $disc_value = 0.0;
            $vat = (float) $odata['vat_rate'];
            $qty = intval($odata['qty']);
            // apply freebies from auto-discounts
            $adapters = $om->read('sale\booking\BookingPriceAdapter', $odata['auto_discounts_ids'], ['type', 'value']);
            foreach($adapters as $aid => $adata) {
                // amount and percent discounts have been applied in ::getUnitPrice()
                if($adata['type'] == 'freebie') {
                    $qty -= $adata['value'];
                }
            }
            // apply additional manual discounts
            $discounts = $om->read('sale\booking\BookingPriceAdapter', $odata['manual_discounts_ids'], ['type', 'value']);
            foreach($discounts as $aid => $adata) {
                if($adata['type'] == 'amount') {
                    $disc_value += $adata['value'];
                }
                else if($adata['type'] == 'percent') {
                    $disc_percent += $adata['value'];
                }
                else if($adata['type'] == 'freebie') {
                    $qty -= $adata['value'];
                }
            }
            $price = ($price * (1-$disc_percent));
            // apply discount amount VAT excl.
            // $result[$oid] = round( (($price * $qty) - $disc_value) * (1 + $vat), 2);
            // apply discount amount VAT incl.
            $result[$oid] = round( ($price * $qty)  * (1 + $vat) - $disc_value, 2);
        }
        return $result;
    }

    public static function getVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
        }
        return $result;
    }

    public static function getConstraints() {
        return [
            'qty' =>  [
                'lte_zero' => [
                    'message'       => 'Quantity must be a positive value.',
                    'function'      => function ($qty, $values) {
                        return ($qty > 0);
                    }
                ]
            ]

        ];
    }
}