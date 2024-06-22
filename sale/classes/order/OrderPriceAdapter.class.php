<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;
use equal\orm\Model;

class OrderPriceAdapter extends Model {

    public static function getName() {
        return "Price Adapter";
    }

    public static function getDescription() {
        return "Adapters allow to adapt the final price of the order lines, either by performing a direct computation, or by using a discount definition.";
    }

    public static function getColumns() {
        return [

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the adapter relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'order_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\OrderLineGroup',
                'description'       => 'Order Line Group the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ],

            'order_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\OrderLine',
                'description'       => 'Order Line the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ],

            'is_manual_discount' => [
                'type'              => 'boolean',
                'description'       => "Flag to set the adapter as manual or related to a discount.",
                'default'           => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'percent',
                    'amount',
                    'freebie'
                ],
                'description'       => 'Type of manual discount (fixed amount or percentage of the price).',
                'visible'           => ['is_manual_discount', '=', true],
                'default'           => 'percent',
                'onupdate'          => 'onupdateValue'
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'value' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => "Value of the discount (monetary amount or percentage).",
                'visible'           => ['is_manual_discount', '=', true],
                'default'           => 0.0,
                'onupdate'          => 'onupdateValue'
            ],

            'discount_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\Discount',
                'description'       => 'Discount related to the adapter, if any.',
                'visible'           => ['is_manual_discount', '=', false]
            ],

            'discount_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountList',
                'description'       => 'Discount List related to the adapter, if any.',
                'visible'           => ['is_manual_discount', '=', false]
            ]
        ];
    }

    public static function onupdateValue($om, $oids, $values, $lang) {
        // reset computed price for related orders and order_line_groups
        $discounts = $om->read(__CLASS__, $oids, ['order_id', 'order_line_id', 'order_line_group_id']);

        if($discounts > 0) {
            $orders_ids = array_map( function($a) { return $a['order_id']; }, $discounts);
            $order_lines_ids = array_map( function($a) { return $a['order_line_id']; }, $discounts);
            $order_line_groups_ids = array_map( function($a) { return $a['order_line_group_id']; }, $discounts);
            $om->update(Order::getType(), $orders_ids, ['price' => null, 'total' => null]);
            $om->callonce(OrderLine::getType(), '_resetPrices', $order_lines_ids, [], $lang);
            $om->callonce(OrderLineGroup::getType(), '_resetPrices', $order_line_groups_ids, [], $lang);
        }
    }

    public static function getConstraints() {
        return [
            'order_line_id' =>  [
                'missing_relation' => [
                    'message'       => 'order_line_id or order_line_group_id must be set.',
                    'function'      => function ($order_line_id, $values) {
                        return ($values['order_line_id'] >= 0 || $values['order_line_group_id'] >=0);
                    }
                ]
            ]
        ];
    }

}