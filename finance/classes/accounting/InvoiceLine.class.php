<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;

class InvoiceLine extends Model {

    public static function getName() {
        return "Invoice line";
    }

    public static function getDescription() {
        return "Invoice lines describe the products and quantities that are part of an invoice.";
    }

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product (computed).',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => 'Complementary description of the line (independant from product).'
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their invoice).',
                'ondelete'          => 'cascade'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Invoice::getType(),
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \sale\price\Price::getType(),
                'description'       => 'The price the line relates to (assigned at line creation).',
                'onupdate'          => 'onupdatePriceId'
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.',
                'function'          => 'finance\accounting\InvoiceLine::calcUnitPrice',
                'store'             => true
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'function'          => 'calcVatRate',
                'store'             => true,
                'default'           => 0.0,
                'onupdate'          => 'onupdateVatRate'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'onupdate'          => 'onupdateQty'
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'onupdate'          => 'onupdateFreeQty'
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'onupdate'          => 'onupdateDiscount'
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
                'description'       => 'Final tax-included price of the line (computed).',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'downpayment_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Invoice::getType(),
                'description'       => 'Downpayment invoice (set when the line refers to an invoiced downpayment.)'
            ]
        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(get_called_class(), $oids, ['product_id.name'], $lang);
        if($lines > 0) {
            foreach($lines as $oid => $line) {
                $result[$oid] = $line['product_id.name'];
            }
        }
        return $result;
    }

    public static function calcUnitPrice($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(__CLASS__, $oids, ['price_id.price']);

        if($lines > 0) {
            foreach($lines as $oid => $line) {
                $result[$oid] = $line['price_id.price'];
            }
        }
        return $result;
    }

    public static function calcVatRate($om, $oids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $oids, ['price_id.accounting_rule_id.vat_rule_id.rate']);
        if($lines > 0) {
            foreach($lines as $oid => $odata) {
                $result[$oid] = 0.0;
                if(isset($odata['price_id.accounting_rule_id.vat_rule_id.rate'])) {
                    $result[$oid] = floatval($odata['price_id.accounting_rule_id.vat_rule_id.rate']);
                }
            }
        }
        return $result;
    }

    /**
     * Get total tax-excluded price of the line.
     *
     */
    public static function calcTotal($om, $oids, $lang) {
        $result = [];

        $lines = $om->read(get_called_class(), $oids, ['qty','unit_price','free_qty','discount']);

        foreach($lines as $oid => $line) {
            $result[$oid] = round($line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']), 4);
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
            $total = round((float) $odata['total'], 4);
            $vat = round((float) $odata['vat_rate'], 4);

            $result[$oid] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }

    public static function onupdatePriceId($om, $oids, $values, $lang) {
        $om->update(get_called_class(), $oids, ['vat_rate' => null, 'unit_price' => null, 'total' => null, 'price' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $oids, [], $lang);
    }

    public static function onupdateVatRate($om, $oids, $values, $lang) {
        $om->update(get_called_class(), $oids, ['price' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $oids, [], $lang);
    }

    public static function onupdateQty($om, $oids, $values, $lang) {
        $om->update(get_called_class(), $oids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $oids, [], $lang);
    }

    public static function onupdateFreeQty($om, $oids, $values, $lang) {
        $om->update(get_called_class(), $oids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $oids, [], $lang);
    }

    public static function onupdateDiscount($om, $oids, $values, $lang) {
        $om->update(get_called_class(), $oids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $oids, [], $lang);
    }

    public static function _resetInvoice($om, $oids, $values, $lang) {
        $lines = $om->read(get_called_class(), $oids, ['invoice_id']);
        if($lines > 0)  {
            $invoices_ids = array_map(function($a) {return $a['invoice_id'];}, $lines);
            $om->update('finance\accounting\Invoice', $invoices_ids, ['price' => null, 'total' => null]);
        }
    }

}