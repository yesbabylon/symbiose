<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\receivable;
use \equal\orm\Model;

class Receivable extends Model {

    public static function getDescription() {
        return "A Sale Receivable represent a good or a service that has been delivered to a Customer and that must be billed.";
    }

    public static function getColumns() {

        return [

            'receivable_queue_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\ReceivablesQueue',
                'description'       => 'The parent Queue the item is attached to.',
                'dependencies'      => ['customer_id']
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'The entry is linked to a Receivable entry.'
            ],

            'invoice_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'description'       => 'The invoice line that has been generated based on the item.'
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer to who refers the item (from ReceivableQueue).',
                'store'             => true,
                'function'          => 'calcCustomerId'
            ],

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

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Invoice the line is related to.',
                'required'          => true,
                'ondelete'          => 'null'
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
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the line.',
                'dependencies'      => ['total', 'price']
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price']
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 0,
                'dependencies'      => ['total', 'price']
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'dependencies'      => ['total', 'price']
            ],

            // #memo - important: to allow maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price']
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

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['receivable_queue_id' => ['customer_id']]);
        foreach($self as $id => $receivable) {
            if($receivable['receivable_queue_id']) {
                $result[$id] = $receivable['receivable_queue_id']['customer_id'];
            }
        }
        return $result;
    }

    /**
     * Get total tax-excluded price of the line.
     *
     */
    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $line) {
            $result[$id] = $line['unit_price'] * (1.0 - $line['discount']) * ($line['qty'] - $line['free_qty']);
        }
        return $result;
    }

    /**
     * Get final tax-included price of the line.
     *
     */
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
}