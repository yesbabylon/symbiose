<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\receivable;

use core\setting\Setting;
use equal\orm\Model;
use sale\price\Price;
use sale\price\PriceList;

class Receivable extends Model {

    public static function getDescription() {
        return 'A Sale Receivable represent a good or a service that has been sold to a Customer, and whose amount must be received.';
    }

    public static function getColumns() {

        return [

            'receivables_queue_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\receivable\ReceivablesQueue',
                'description'       => 'The parent Queue the receivable is attached to.',
                'required'          => true,
                'dependencies'      => ['customer_id'],
                'readonly'          => true
            ],

            'customer_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The Customer to who refers the item (from ReceivableQueue).',
                'store'             => true,
                'function'          => 'calcCustomerId',
                'readonly'          => true
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Creation date of the receivable.',
                'required'          => true,
                'default'           => time(),
                'dependencies'      => ['price_id'],
                'readonly'          => true
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Default label of the line, based on product.',
                'function'          => 'calcName',
                'store'             => true,
                'readonly'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Description of the receivable.',
                'readonly'          => true
            ],

            'status' => [
                'type'              => 'string',
                'description'       => 'Status of the receivable. It can be pending, invoiced or cancelled.',
                'selection'         => [
                    'pending',
                    'invoiced',
                    'cancelled'
                ],
                'default'           => 'pending'
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the receivable relates to.',
                'required'          => true,
                'dependencies'      => ['price_id'],
                'readonly'          => true
            ],

            'price_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the receivable relates to.',
                'dependencies'      => ['unit_price'],
                'function'          => 'calcPriceId',
                'store'             => true,
                'readonly'          => true
            ],

            'unit_price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the receivable.',
                'dependencies'      => ['total', 'price'],
                'function'          => 'calcUnitPrice',
                'store'             => true,
                'readonly'          => true
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'VAT rate to be applied.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price'],
                'readonly'          => true
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product.',
                'default'           => 1.0,
                'dependencies'      => ['total', 'price'],
                'readonly'          => true
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity of product, if any.',
                'default'           => 0,
                'dependencies'      => ['total', 'price'],
                'readonly'          => true
            ],

            // #memo - important: to allow maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0,
                'dependencies'      => ['total', 'price'],
                'readonly'          => true
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the receivable.',
                'function'          => 'calcTotal',
                'store'             => true,
                'readonly'          => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price of the receivable.',
                'function'          => 'calcPrice',
                'store'             => true,
                'readonly'          => true
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'description'       => 'Invoice the receivable is related to.',
                'ondelete'          => 'null'
            ],

            'invoice_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'description'       => 'The invoice line that has been generated based on the item.',
                'ondelete'          => 'null'
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['product_id' => ['name']]);
        foreach($self as $id => $receivable) {
            if(isset($receivable['product_id']['name'])) {
                $result[$id] = $receivable['product_id']['name'];
            }
        }

        return $result;
    }

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['receivables_queue_id' => ['customer_id']]);
        foreach($self as $id => $receivable) {
            if(isset($receivable['receivables_queue_id']['customer_id'])) {
                $result[$id] = $receivable['receivables_queue_id']['customer_id'];
            }
        }

        return $result;
    }

    public static function calcPriceId($self) {
        $result = [];
        $self->read(['date', 'product_id']);
        foreach($self as $id => $receivable) {
            $price_lists_ids = PriceList::search([
                [
                    ['date_from', '<=', $receivable['date']],
                    ['date_to', '>=', $receivable['date']],
                    ['status', '=', 'published'],
                ]
            ])
                ->ids();

            if(empty($price_lists_ids)) {
                continue;
            }

            $price = Price::search([
                ['product_id', '=', $receivable['product_id']],
                ['price_list_id', 'in', $price_lists_ids]
            ])
                ->read(['id'])
                ->first();

            if(isset($price)) {
                $result[$id] = $price['id'];
            }
        }

        return $result;
    }

    public static function calcUnitPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $receivable) {
            if(isset($receivable['price_id']['price'])) {
                $result[$id] = $receivable['price_id']['price'];
            }
        }

        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty', 'unit_price', 'free_qty', 'discount']);
        foreach($self as $id => $receivable) {
            $result[$id] = $receivable['unit_price'] * (1.0 - $receivable['discount']) * ($receivable['qty'] - $receivable['free_qty']);
        }

        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total', 'vat_rate']);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);
        foreach($self as $id => $receivable) {
            $total = (float) $receivable['total'];
            $vat = (float) $receivable['vat_rate'];
            $result[$id] = round($total * (1.0 + $vat), $currency_decimal_precision);
        }

        return $result;
    }
}