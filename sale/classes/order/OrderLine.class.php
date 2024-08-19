<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;
use equal\orm\Model;
use sale\catalog\Product;
use sale\price\Price;

class OrderLine extends Model {

    public static function getName() {
        return "Order line";
    }

    public static function getDescription() {
        return "Order lines describe the products and quantities that are part of a order.";
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

            'order_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\OrderLineGroup',
                'description'       => 'Group the line relates to (in turn, groups relate to their order).',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'The order the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\Product',
                'description'       => 'The product (SKU) the line relates to.'
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\catalog\ProductModel',
                'description'       => 'The product model the line relates to (from product).',
            ],

            'price_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\price\Price',
                'description'       => 'The price the line relates to (retrieved by price list).',
                'dependents'        => ['unit_price' , 'vat_rate']
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the receivable.',
                'dependents'        => ['total', 'price']
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'VAT rate that applies to this line.',
                'dependents'        => ['total', 'price'],
                'function'          => 'calcVatRate',
                'store'             => true,
                'instant'           => true
            ],

            'price_adapters_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\OrderPriceAdapter',
                'foreign_field'     => 'order_line_id',
                'description'       => 'All price adapters: auto and manual discounts applied on the line.'
            ],

            'qty' => [
                'type'              => 'float',
                'description'       => 'Quantity of product items for the line.',
                'dependents'        => ['total', 'price'],
                'default'           => 0
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the line have to be sorted when presented visually.',
                'default'           => 1
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'discount' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => 'Total amount of discount to apply, if any.',
                'default'           => 0.0
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the line (computed).',
                'function'          => 'calcTotal',
                'store'             => true,
                'instant'           => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price (computed).',
                'function'          => 'calcPrice',
                'store'             => true,
                'instant'           => true
            ],

            'fare_benefit' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Total amount of the fare benefit VAT incl.',
                'function'          => 'calcFareBenefit',
                'store'             => true,
                'instant'           => true
            ]

        ];
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['order_line_group_id'])) {
            $order_line_group = OrderLineGroup::id($event['order_line_group_id'])->read(['order_id' => ['id', 'name']])->first(true);
            $result['order_id'] = $order_line_group['order_id'];
        }

        if(isset($event['product_id'])) {
            $product = Product::id($event['product_id'])->read(['product_model_id' => ['id', 'name']])->first(true);
            $result['product_model_id'] = $product['product_model_id'];
        }

        if(isset($event['price_id'])) {
            $price = Price::id($event['price_id'])->read(['price', 'vat_rate'])->first(true);
            $result['unit_price'] = $price['price'];
            $result['vat_rate'] = $price['vat_rate'];
        }


        if($result['unit_price'] || isset($event['qty']) || isset($event['free_qty']) || isset($event['discount'])){
            $price = Price::id($values['price_id'])->read(['price'])->first(true);
            $qty =  $event['qty'] ?? $values['qty'];
            $free_qty = $event['free_qty'] ??  $values['free_qty'];
            $discount = $event['discount'] ??  $values['discount'];
            $vat_rate = $event['vat_rate'] ??  $values['vat_rate'];
            $unit_price =  $event['unit_price'] ??  $values['unit_price'];

            $result['fare_benefit'] = self::calculateFareBenefit($free_qty,$qty,$price['price'],$vat_rate,$unit_price);

            $total = self::calculateTotal($unit_price , $qty, $free_qty,  $discount);
            $result['total'] = $total;
            $result['price'] = self::calculatePrice($total, $vat_rate);
        }

        return $result;
    }

    public static function calcVatRate($self) {
        $result = [];
        $self->read(['price_id' => ['vat_rate']]);
        foreach($self as $id => $line) {
            if(isset($line['price_id']['vat_rate'])) {
                $result[$id] = $line['price_id']['vat_rate'];
            }
        }

        return $result;
    }

    public static function calcTotal($self) {
        $result = [];
        $self->read(['qty','unit_price','free_qty','discount']);
        foreach($self as $id => $line) {
            $result[$id] = self::calculateTotal($line['unit_price'],$line['qty'], $line['free_qty'],  $line['discount']);
        }
        return $result;
    }

    public static function calculateTotal($unit_price, $qty, $free_qty, $discount) {
        return round($unit_price * (1.0 - $discount) * ($qty - $free_qty), 4);
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['total','vat_rate']);
        foreach($self as $id => $line) {
            $result[$id] = self::calculatePrice($line['total'], $line['vat_rate']);
        }
        return $result;
    }

    public static function calculatePrice($total, $vat_rate) {
        return round( $total  * (1.0 + $vat_rate), 2);
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['product_id']);
        foreach($self as $id => $line) {
            $product = Product::id($line['product_id'])->read(['name'])->first(true);
            $result[$id] = $product['name'];
        }
        return $result;
    }

    public static function calcFareBenefit($self) {
        $result = [];
        $self->read(['free_qty', 'qty', 'price_id', 'vat_rate', 'unit_price']);
        foreach($self as $id => $line) {
            $price = Price::id($line['price_id'])->read(['price'])->first(true);
            $result[$id] = self::calculateFareBenefit($line['free_qty'],$line['qty'],
                                                      $price['price'],$line['vat_rate'],
                                                      $line['unit_price']);
        }
        return $result;
    }

    public static function calculateFareBenefit($free_qty, $qty, $price, $vat_rate, $unit_price ) {
        $catalog_price = $price * $qty * (1.0 + $vat_rate);
        $fare_price = $unit_price * ($qty - $free_qty) * (1.0 + $vat_rate);
        $benefit = round($catalog_price - $fare_price, 2);
        return max(0.0, $benefit);
    }

}