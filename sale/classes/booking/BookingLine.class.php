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

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
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

            'free_qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Free quantity.',
                'function'          => 'calcFreeQty',
                'store'             => true
            ],

            'discount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
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
                'function'          => 'getVatRate',
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
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    public static function onupdateQty($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    public static function onupdateUnitPrice($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(__CLASS__, '_resetPrices', $oids, ['total' => null, 'price' => null, 'fare_benefit' => null], $lang);
    }

    public static function onupdateVatRate($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(__CLASS__, '_resetPrices', $oids, ['total' => null, 'price' => null, 'fare_benefit' => null], $lang);
    }

    public static function onupdatePriceId($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    public static function onupdatePriceAdaptersIds($om, $oids, $values, $lang) {
        // reset computed fields related to price
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }


    // reset computed fields related to price
    public static function _resetPrices($om, $oids, $values, $lang) {
        $new_values = ['vat_rate' => null, 'unit_price' => null, 'total' => null, 'price' => null, 'fare_benefit' => null, 'discount' => null, 'free_qty' => null];
        if(count($values)) {
            // list of fields being reset can be customized by caller
            // #memo - vat_rate and unit_price can be set manually, we don't want to overwrite the update !
            $new_values = $values;
        }
        $om->write(__CLASS__, $oids, $new_values);
        // update parent objects
        $lines = $om->read(__CLASS__, $oids, ['booking_line_group_id'], $lang);
        if($lines > 0) {
            $booking_line_groups_ids = array_map(function ($a) { return $a['booking_line_group_id']; }, array_values($lines));
            $om->callonce(\sale\booking\BookingLineGroup::getType(), '_resetPrices', $booking_line_groups_ids, [], $lang);
        }
    }

    /**
     * This method is called upon change on: qty
     */
    public static function _createConsumptions($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling sale\booking\BookingLine:_createConsumptions", QN_REPORT_DEBUG);
        // done upon status change : when booking status is set to 'option'
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
                }
                $result[$oid] = round(($price * (1-$disc_percent)) - $disc_value, 2);
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

        $lines = $om->read(get_called_class(), $oids, ['manual_discounts_ids']);

        foreach($lines as $oid => $odata) {
            $result[$oid] = 0;
            // apply additional manual discounts
            $discounts = $om->read('sale\booking\BookingPriceAdapter', $odata['manual_discounts_ids'], ['type', 'value']);
            foreach($discounts as $aid => $adata) {
                if($adata['type'] == 'percent') {
                    $result[$oid] += $adata['value'];
                }
            }
        }
        return $result;
    }

    public static function calcFareBenefit($om, $oids, $lang) {
        $result = [];
        // #memo - price adapters are already applied on unit_price (so we need price_id)
        $lines = $om->read(get_called_class(), $oids, ['free_qty', 'qty', 'price_id.price', 'vat_rate', 'price']);
        if($lines) {
            foreach($lines as $lid => $line) {
                // delta between final price and catalog price
                $benefit = ( $line['price_id.price'] * ($line['qty']-$line['free_qty']) * (1.0+$line['vat_rate']) ) - $line['price'];
                // add vat_incl value of the free products
                $benefit += $line['price_id.price'] * $line['free_qty'] * (1.0+$line['vat_rate']);
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
            $price = (float) $odata['total'];
            $vat = (float) $odata['vat_rate'];

            $result[$oid] = round( $price  * (1.0 + $vat), 2);
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
                    'auto_discounts_ids',
                    'manual_discounts_ids',
                    'payment_mode'
                ]);

        foreach($lines as $oid => $odata) {

            if($odata['payment_mode'] == 'free') {
                $result[$oid] = 0;
                continue;
            }

            $price = (float) $odata['unit_price'];
            $disc_percent = 0.0;
            $disc_value = 0.0;
            $qty = intval($odata['qty']);
            // apply freebies from auto-discounts
            $adapters = $om->read('sale\booking\BookingPriceAdapter', $odata['auto_discounts_ids'], ['type', 'value']);
            foreach($adapters as $aid => $adata) {
                // amount and percent discounts have been applied in ::calcUnitPrice()
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
            // apply discount amount VAT excl.
            $price = ($price * (1.0-$disc_percent)) - $disc_value;

            $result[$oid] = max(0, $price * $qty);
        }

        return $result;
    }


    public static function getVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
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