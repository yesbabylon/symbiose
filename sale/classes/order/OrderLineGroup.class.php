<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\order;
use equal\orm\Model;

class OrderLineGroup extends Model {

    public static function getName() {
        return "Order line group";
    }

    public static function getDescription() {
        return "Order line groups are related to a order and describe.";
    }

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'string',
                'description'       => 'Memo for the group.',
                'default'           => ''
            ],

            'order_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\order\OrderLine',
                'foreign_field'     => 'order_line_group_id',
                'description'       => 'Order lines that belong to the group.',
                'ondetach'          => 'delete',
                'dependents'        => ['total', 'price',  'order_id' => ['total', 'price']]
            ],

            'order_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\order\Order',
                'description'       => 'Order the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price for all lines (computed).',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included price for all lines (computed).',
                'function'          => 'calcPrice',
                'store'             => true
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

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['order_id','order_lines_ids' => ['total']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce($group['order_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['order_lines_ids' => ['price']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce($group['order_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
        }
        return $result;
    }

    public static function calcFareBenefit($self): array {
        $result = [];
        $self->read(['order_lines_ids' => ['fare_benefit']]);
        foreach($self as $id => $group) {
            $result[$id] = array_reduce($group['order_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['fare_benefit'];
            }, 0.0);
        }
        return $result;
    }

    public static function candelete($self, $values) {

        $self->read(['order_id']);
        foreach($self as $group) {
            Order::id($group['order_id'])
                ->update([
                    'price' => null,
                    'total' => null
                ]);
        }

        return parent::candelete($self, $values);
    }

}