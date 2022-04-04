<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pos;
use equal\orm\Model;

class OrderLine extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'string',
                'description'       => 'The name of the good or service being sold.'
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\Order',
                'description'       => 'The operation the payment relates to.'
            ],

            'order_payment_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pos\OrderPayment',
                'description'       => 'The payement the line relates to.',
                'default'           => 0
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.'
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Tax-excluded unit price (with automated discounts applied).',
                'onchange'          => '_resetPrice'
            ],

            'vat_rate' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => 'VAT rate that applies to this line.',
                'default'           => 0.0,
                'onchange'          => '_resetPrice'
            ],

            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => 'Discount rate to apply on the unit price.',
                'default'           => 0.0,
                'onchange'          => '_resetPrice'
            ],

            'qty' => [
                'type'              => 'integer',
                'usage'             => 'numeric/integer',
                'description'       => 'Amount of units in this line.',
                'default'           => 0.0,
                'onchange'          => '_resetPrice'
            ],

            'free_qty' => [
                'type'              => 'integer',
                'usage'             => 'numeric/integer',
                'description'       => 'Amount of freebies in this line.',
                'default'           => 0.0,
                'onchange'          => '_resetPrice'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'getTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money',                
                'description'       => 'Final tax-included price of the line (computed).',
                'function'          => 'getPrice',
                'store'             => true
            ]

        ];
    }

    public static function getTotal($om, $ids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $ids, ['unit_price', 'qty', 'free_qty', 'discount']);
        if($lines > 0) {
            foreach($lines as $lid => $line) {
                $result[$lid] = round(($line['unit_price'] * (1 - $line['discount'])) * ($line['qty'] - $line['free_qty']), 2);
            }
        }
        return $result;
    }

    public static function getPrice($om, $ids, $lang) {
        $result = [];
        $lines = $om->read(__CLASS__, $ids, ['total', 'vat_rate']);
        if($lines > 0) {
            foreach($lines as $lid => $line) {
                $result[$lid] = round($line['total'] * (1 + $line['vat_rate']), 2);
            }
        }
        return $result;
    }

    public static function _resetPrice($om, $ids, $lang) {
        $lines = $om->read(get_called_class(), $ids, ['order_id'], $lang);
        if($lines > 0) {
            $orders_ids = array_reduce($lines, function ($c, $o) { return array_merge($c, [$o['order_id']]); }, [] );
            $om->write('sale\pos\Order', $orders_ids, ['total' => null, 'price' => null], $lang);
        }
        $om->write(get_called_class(), $ids, ['total' => null, 'price' => null], $lang);
    }
}