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
use sale\price\PriceList;

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
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'The order the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'function'          => 'calcOrder',
                'store'             => true,
                'instant'           => true,
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
                'dependents'        => ['total', 'price', 'vat_rate','order_id' => ['total', 'price'], 'order_line_group_id' => ['total', 'price']]
            ],

            'unit_price' => [
                'type'              => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Unit price of the product related to the receivable.',
                'dependents'        => ['total', 'price', 'vat_rate','order_id' => ['total', 'price'], 'order_line_group_id' => ['total', 'price']]
            ],

            'vat_rate' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'VAT rate that applies to this line.',
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
                'default'           => 0,
                'dependents'        => ['total', 'price', 'vat_rate','order_id' => ['total', 'price'], 'order_line_group_id' => ['total', 'price']]
            ],

            'free_qty' => [
                'type'              => 'integer',
                'description'       => 'Free quantity.',
                'default'           => 0,
                'dependents'        => ['total', 'price', 'vat_rate','order_id' => ['total', 'price'], 'order_line_group_id' => ['total', 'price']]
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the line have to be sorted when presented visually.',
                'default'           => 1
            ],

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

            $line = OrderLineGroup::id($values['order_line_group_id'])->read(['order_id' => ['id', 'name', 'delivery_date']])->first(true);
            $date_from = strtotime(date('Y-01-01 00:00:00', $line['order_id']['delivery_date']));
            $date_to = strtotime(date('Y-12-31 23:59:59', $line['order_id']['delivery_date']));

            $price = self::getProductPrice(
                $event['product_id'],
                $date_from ,
                $date_to
            );

            $result['price_id'] = $price;
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

            $result['fare_benefit'] = self::calculateFareBenefit($free_qty, $qty, $price['price'], $vat_rate, $unit_price);

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

    public static function calcOrder($self) {
        $result = [];
        $self->read(['order_line_group_id']);
        foreach($self as $id => $line) {
            $order_line_group = OrderLineGroup::id($line['order_line_group_id'])->read(['order_id' => ['id', 'name']])->first(true);
            $result[$id] = $order_line_group['order_id'];
        }
        return $result;
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

    public static function getProductPrice($product_id, $date_from, $date_to) {
        $price = null;

        $price_lists_ids = self::getPriceListsIds($date_from, $date_to);
        if(!empty($price_lists_ids)) {
            $price = Price::search([
                ['product_id', '=', $product_id],
                ['price_list_id', 'in', $price_lists_ids]
            ])
                ->read(['id', 'name', 'price' ,'vat_rate'])
                ->first();
        }

        return $price;
    }

    public static function getPriceListsIds($date_from, $date_to) {
        return PriceList::search([
            [
                ['date_from', '<', $date_from],
                ['date_to', '>=', $date_from],
                ['date_to', '<=', $date_to],
                ['status', '=', 'published'],
            ],
            [
                ['date_from', '>=', $date_from],
                ['date_to', '>=', $date_from],
                ['date_to', '<=', $date_to],
                ['status', '=', 'published'],
            ],
            [
                ['date_from', '>=', $date_from],
                ['date_to', '>', $date_to],
                ['status', '=', 'published'],
            ],
            [
                ['date_from', '<', $date_from],
                ['date_to', '>', $date_to],
                ['status', '=', 'published'],
            ]
        ])
        ->ids();
    }


    public static function canupdate($self, $values): array {
        $self->read(['id','order_id','order_line_group_id', 'qty', 'free_qty']);
        foreach($self as $line) {
            if(isset($values['order_line_group_id'])) {
                $group = OrderLineGroup::id($values['order_line_group_id'])
                    ->read(['order_id'])
                    ->first(true);

                if($group['order_id'] != $values['order_id']) {
                    return ['order_line_group_id' => ['invalid_param' => 'Group must be linked to same order.']];
                }
            }

            if(isset($values['order_id'])) {
                $order = Order::id($values['order_id'])->read(['status'])->first(true);

                if (!in_array($order['status'], ['quote','checkedin', 'checkedout'])) {
                    return ['order_line_group_id' => ['non_editable' => 'The order edition is limited.']];
                }

            }

            if(isset($values['qty'])) {
                if($values['qty'] <= 0) {
                    return ['qty' => ['must_be_greater_than_zero' => 'Quantity must be greater than 0.']];
                }

                $free_qty = $values['free_qty'] ?? $line['free_qty'];
                if($values['qty'] <= $free_qty) {
                    return ['qty' => ['must_be_greater_than_free_qty' => 'Quantity must be greater than free quantity.']];
                }
            }

            if(isset($values['free_qty'])) {
                if($values['free_qty'] < 0) {
                    return ['free_qty' => ['must_be_greater_than_or_equal_to_zero' => 'Free quantity must be greater than or equal to 0.']];
                }

                $qty = $values['qty'] ?? $line['qty'];
                if($values['free_qty'] >= $qty) {
                    return ['free_qty' => ['must_be_lower_than_qty' => 'Free quantity must be lower than quantity.']];
                }
            }

            if(isset($values['unit_price']) && $values['unit_price'] <= 0) {
                return ['unit_price' => ['must_be_greater_than_zero' => 'Unit price must be greater than 0.']];
            }

            if(isset($values['discount'])) {
                if($values['discount'] < 0) {
                    return ['discount' => ['must_be_greater_than_zero' => 'Discount must be greater than or equal to 0%.']];
                }
                if($values['discount'] > 0.99) {
                    return ['discount' => ['must_be_lower_than_one' => 'Discount must be lower than 100%.']];
                }
            }
        }

        return parent::canupdate($self, $values);
    }
}