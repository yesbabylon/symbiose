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
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Line name relates to its product.',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line. If set, replaces the product name.',
                'default'           => ''
            ],

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
                'onupdate'          => 'onupdateProductId'
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => 'The product model the line relates to (from product).',
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \sale\price\Price::getType(),
                'description'       => 'The price the line relates to (retrieved by price list).',
                'onupdate'          => 'onupdatePriceId'
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
                'onupdate'          => 'onupdatePriceAdaptersIds'
            ],

            // automatic price adapters are used for computing the unit_price
            'auto_discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'domain'            => ['is_manual_discount', '=', false],
                'description'       => 'Price adapters relating to auto discounts only.'
            ],

            // manual discounts are used for computing the resulting discount rate (except freebies)
            'manual_discounts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingPriceAdapter',
                'foreign_field'     => 'booking_line_id',
                'domain'            => ['is_manual_discount', '=', true],
                'description'       => 'Price adapters relating to manual discounts only.',
                'onupdate'          => 'onupdatePriceAdaptersIds'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product items for the line.',
                'default'           => 1.0,
                'onupdate'          => 'onupdateQty'
            ],

            'has_own_qty' => [
                'type'              => 'boolean',
                'description'       => 'Set according to related pack line.',
                'default'           => false
            ],

            'has_own_duration' => [
                'type'              => 'boolean',
                'description'       => 'Set according to related pack line.',
                'default'           => false
            ],

            'own_duration' => [
                'type'              => 'integer',
                'description'       => "Self assigned duration, in days (from pack line).",
                'visible'           => ['has_own_duration', '=', true]
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the line have to be sorted when presented visually.',
                'default'           => 1
            ],

            'payment_mode' => [
                'type'              => 'string',
                'selection'         => [
                    'invoice',                  // consumption has to be added to an invoice
                    'cash',                     // consumption is paid in cash (money or bank transfer)
                    'free'                      // related consumption is a gift
                ],
                'default'           => 'invoice',
                'description'       => 'The way the line is intended to be paid.',
            ],

            'is_contractual' => [
                'type'              => 'boolean',
                'description'       => 'Is the line part of the original contract (or added afterward)?',
                'default'           => false
            ],

            'is_invoiced' => [
                'type'              => 'boolean',
                'description'       => 'Is the line part of the original contract (or added afterward)?',
                'default'           => false
            ],

            // freebies are from both automatic price adapters and manual discounts
            'free_qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Free quantity.',
                'function'          => 'calcFreeQty',
                'store'             => true
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of manual discount to apply, if any.',
                'function'          => 'calcDiscount',
                'store'             => true
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded unit price (with automated discounts applied).',
                'function'          => 'calcUnitPrice',
                'store'             => true,
                'onupdate'          => 'onupdateUnitPrice'
            ],

            'has_manual_unit_price' => [
                'type'              => 'boolean',
                'description'       => 'Flag indicating that the unit price has been set manually and must not be reset in case of price reset.',
                'default'           => false
            ],

            'has_manual_vat_rate' => [
                'type'              => 'boolean',
                'description'       => 'Flag indicating that the vat rate price has been set manually and must not be reset in case of price reset.',
                'default'           => false
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'description'       => 'VAT rate that applies to this line.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'onupdate'          => 'onupdateVatRate'
            ],

            'fare_benefit' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total amount of the fare banefit VAT incl.',
                'function'          => 'calcFareBenefit',
                'store'             => true
            ]

        ];
    }

    public static function onupdateProductId($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
        // update product model according to newly set product
        $lines = $om->read(self::getType(), $oids, ['product_id.product_model_id'], $lang);
        foreach($lines as $lid => $line) {
            $om->update(self::getType(), $lid, ['product_model_id' => $line['product_id.product_model_id']]);
        }
    }

    public static function onupdateQty($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
    }

    /**
     * Handler for unit_price field update.
     * Resets computed fields related to price.
     */
    public static function onupdateUnitPrice($om, $oids, $values, $lang) {
        $om->update(self::getType(), $oids, ['has_manual_unit_price' => true], $lang);
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
    }

    public static function onupdateVatRate($om, $oids, $values, $lang) {
        // mark line with manual vat_rate
        $om->update(self::getType(), $oids, ['has_manual_vat_rate' => true], $lang);
        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
    }

    public static function onupdatePriceId($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
    }

    public static function onupdatePriceAdaptersIds($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(self::getType(), '_resetPrices', $oids, $values, $lang);
    }

    /**
     * Reset computed fields related to price.
     */
    public static function _resetPrices($om, $oids, $values, $lang) {
        trigger_error("ORM::calling sale\booking\BookingLine:_resetPrices", QN_REPORT_DEBUG);

        $lines = $om->read(self::getType(), $oids, ['price_id', 'has_manual_unit_price', 'has_manual_vat_rate', 'booking_line_group_id'], $lang);

        if($lines > 0) {
            $new_values = ['vat_rate' => null, 'unit_price' => null, 'total' => null, 'price' => null, 'fare_benefit' => null, 'discount' => null, 'free_qty' => null];
            // #memo - computed fields (eg. vat_rate and unit_price) can also be set manually, in such case we don't want to overwrite the assigned value
            if(count($values)) {
                $fields = array_keys($new_values);
                foreach($values as $field => $value) {
                    if(in_array($field, $fields) && !is_null($value)) {
                        $new_values[$field] = $value;
                    }
                }
            }

            // update lines
            foreach($lines as $lid => $line) {
                $assigned_values = $new_values;
                // don't reset unit_price for products that have a manual unit price set or that are not linked to a Price object
                if($line['has_manual_unit_price'] || !$line['price_id']) {
                    unset($assigned_values['unit_price']);
                }
                // don't reset vat_rate for products that have a manual vat rate set or that are not linked to a Price object
                if($line['has_manual_vat_rate'] || !$line['price_id']) {
                    unset($assigned_values['vat_rate']);
                }
                $om->update(self::getType(), $lid, $assigned_values);
            }

            // update parent objects
            $booking_line_groups_ids = array_map(function ($a) { return $a['booking_line_group_id']; }, array_values($lines));
            $om->callonce(\sale\booking\BookingLineGroup::getType(), '_resetPrices', $booking_line_groups_ids, [], $lang);
        }
    }

    /**
     * For BookingLines the display name is the name of the product it relates to.
     *
     */
    public static function calcName($om, $oids, $lang) {
        $result = [];
        $res = $om->read(get_called_class(), $oids, ['product_id.name'], $lang);
        foreach($res as $oid => $odata) {
            $result[$oid] = $odata['product_id.name'];
        }
        return $result;
    }

    /**
     * Compute the VAT excl. unit price of the line, with automated discounts applied.
     *
     */
    public static function calcUnitPrice($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, [
                    'price_id.price',
                    'auto_discounts_ids'
                ]);
        if($lines > 0) {
            foreach($lines as $oid => $odata) {
                $price = 0;
                if($odata['price_id.price']) {
                    $price = (float) $odata['price_id.price'];
                }
                $disc_percent = 0.0;
                $disc_value = 0.0;
                if(isset($odata['auto_discounts_ids']) && $odata['auto_discounts_ids']) {
                    $adapters = $om->read('sale\booking\BookingPriceAdapter', $odata['auto_discounts_ids'], ['type', 'value', 'discount_id.discount_list_id.rate_max']);
                    if($adapters > 0) {
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
                    // #memo - when price is adapted, it no longer holds more than 2 decimals (so that unit_price x qty = displayed price)
                    $price = round(($price * (1 - $disc_percent)) - $disc_value, 2);
                }
                // if no adapters, leave price given from price_id (might have more than 2 decimal digits)
                $result[$oid] = $price;
            }
        }
        return $result;
    }


    public static function calcFreeQty($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, ['auto_discounts_ids','manual_discounts_ids']);

        foreach($lines as $oid => $odata) {
            $free_qty = 0;

            $adapters = $om->read('sale\booking\BookingPriceAdapter', $odata['auto_discounts_ids'], ['type', 'value']);
            foreach($adapters as $aid => $adata) {
                if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
            // check additional manual discounts
            $discounts = $om->read('sale\booking\BookingPriceAdapter', $odata['manual_discounts_ids'], ['type', 'value']);
            foreach($discounts as $aid => $adata) {
                if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
            $result[$oid] = $free_qty;
        }
        return $result;
    }

    public static function calcDiscount($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(self::getType(), $oids, ['manual_discounts_ids', 'unit_price']);

        foreach($lines as $oid => $line) {
            $result[$oid] = (float) 0.0;
            // apply additional manual discounts
            $discounts = $om->read('sale\booking\BookingPriceAdapter', $line['manual_discounts_ids'], ['type', 'value']);
            foreach($discounts as $aid => $adata) {
                if($adata['type'] == 'percent') {
                    $result[$oid] += $adata['value'];
                }
                else if($adata['type'] == 'amount' && $line['unit_price'] != 0) {
                    // amount discount is converted to a rate
                    $result[$oid] += round($adata['value'] / $line['unit_price'], 4);
                }
            }
        }
        return $result;
    }

    public static function calcFareBenefit($om, $oids, $lang) {
        $result = [];
        // #memo - price adapters are already applied on unit_price, so we need price_id
        $lines = $om->read(get_called_class(), $oids, ['free_qty', 'qty', 'price_id.price', 'vat_rate', 'unit_price']);
        if($lines) {
            foreach($lines as $lid => $line) {
                // delta between final price and catalog price
                $catalog_price = $line['price_id.price'] * $line['qty'] * (1.0 + $line['vat_rate']);
                $fare_price = $line['unit_price'] * ($line['qty'] - $line['free_qty']) * (1.0 + $line['vat_rate']);
                $benefit = round($catalog_price - $fare_price, 2);
                $result[$lid] = max(0.0, $benefit);
            }
        }
        return $result;
    }

    /**
     * Get final tax-included price of the line.
     *
     */
    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(get_called_class(), $oids, ['total','vat_rate']);

        foreach($lines as $oid => $odata) {
            $result[$oid] = round($odata['total'] * (1.0 + $odata['vat_rate']), 2);
        }
        return $result;
    }

    /**
     * Get total tax-excluded price of the line, with all discounts applied.
     *
     */
    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, [
                    'qty',
                    'unit_price',
                    'free_qty',
                    'discount',
                    'payment_mode'
                ]);
        if($lines > 0) {
            foreach($lines as $oid => $line) {

                if($line['payment_mode'] == 'free') {
                    $result[$oid] = 0.0;
                    continue;
                }

                $result[$oid] = round($line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']), 4);
            }
        }

        return $result;
    }


    public static function calcVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(self::getType(), $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        foreach($lines as $oid => $odata) {
            $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
        }
        return $result;
    }

    public static function getConstraints() {
        return [
            /*
            // #memo - qty can be negative for cancelling/adapting initially booked services (typically in is_extra groups)
            'qty' =>  [
                'lte_zero' => [
                    'message'       => 'Quantity must be a positive value.',
                    'function'      => function ($qty, $values) {
                        return ($qty > 0);
                    }
                ]
            ]
            */
        ];
    }
}