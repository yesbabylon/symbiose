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
                'description'       => 'Complementary description of the line (independent from product).'
            ],

            'invoice_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'description'       => 'Group related (to their invoice) for lines.',
                'ondelete'          => 'cascade'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'onupdate'          => 'onupdateInvoiceId',
                'ondelete'          => 'cascade'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.',
                'required'          => true,
                'dependencies'      => ['name']
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
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

            // #memo - important: to allow maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
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
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Downpayment invoice (for invoiced downpayment).'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['product_id']['name'];
        }
        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['price_id']['price'];
        }
        return $result;
    }

    public static function calcVatRate($self) {
        $result = [];
        $self->read(['price_id' => ['accounting_rule_id' => ['vat_rule_id' => ['rate']]]]);
        foreach($self as $id => $line) {
            $result[$id] = 0.0;
            if(isset($line['price_id']['accounting_rule_id']['vat_rule_id']['rate'])) {
                $result[$id] = floatval($line['price_id']['accounting_rule_id']['vat_rule_id']['rate']);
            }
        }
        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $line) {
            $result[$id] = $line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']);
        }
        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total','vat_rate']);
        foreach($self as $id => $line) {
            $total = (float) $line['total'];
            $vat = (float) $line['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), 2);
        }
        return $result;
    }

    public static function onupdateInvoiceId($om, $ids, $values, $lang) {
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function onupdatePriceId($om, $ids, $values, $lang) {
        $om->update(get_called_class(), $ids, ['vat_rate' => null, 'unit_price' => null, 'total' => null, 'price' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function onupdateVatRate($om, $ids, $values, $lang) {
        $om->update(get_called_class(), $ids, ['price' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function onupdateQty($om, $ids, $values, $lang) {
        $om->update(get_called_class(), $ids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function onupdateFreeQty($om, $ids, $values, $lang) {
        $om->update(get_called_class(), $ids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function onupdateDiscount($om, $ids, $values, $lang) {
        $om->update(get_called_class(), $ids, ['price' => null, 'total' => null]);
        // reset parent invoice computed values
        $om->callonce(self::getType(), '_resetInvoice', $ids, [], $lang);
    }

    public static function _resetInvoice($om, $ids, $values, $lang) {
        $lines = $om->read(get_called_class(), $ids, ['invoice_id']);
        if($lines > 0)  {
            $invoices_ids = array_map(function($a) {return $a['invoice_id'];}, $lines);
            $om->update('finance\accounting\Invoice', $invoices_ids, ['price' => null, 'total' => null]);
        }
    }
}