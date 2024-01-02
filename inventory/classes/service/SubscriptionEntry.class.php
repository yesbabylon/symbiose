<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

class SubscriptionEntry extends \sale\SaleEntry {

    public function getTable() {
        // force table name to use distinct tables and ID columns
        return 'inventory_service_subscriptionentry';
    }

    public static function getColumns() {
        return [

            'subscription_id' => [
                'type'           => 'many2one',
                'required'       => true,
                'foreign_object' => 'inventory\service\Subscription',
                'description'    => 'Subscription of the entry.'
            ],

            'date_from' => [
                'type'           => 'date',
                'description'    => 'Start date of the subscription period this entry covers.',
                'required'       => true
            ],

            'date_to' => [
                'type'           => 'date',
                'description'    => 'End date of the subscription period this entry covers.',
                'required'       => true
            ],

            'is_billable' => [
                'type'           => 'computed',
                'result_type'    => 'boolean',
                'description'    => 'Can be billed to the customer.',
                'store'          => true,
                'function'       => 'calcIsBillable'
            ],

            'customer_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'The Customer to who refers the item.',
                'store'          => true,
                'function'       => 'calcCustomerId'
            ],

            'product_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\catalog\Product',
                'description'    => 'Product of the catalog sale.',
                'store'          => true,
                'function'       => 'calcProductId'
            ],

            'price_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'sale\price\Price',
                'description'    => 'Price of the subscription entry.',
                'dependencies'   => ['price'],
                'store'          => true,
                'function'       => 'calcPriceId'
            ],

            'price' => [
                'type'           => 'computed',
                'result_type'    => 'float',
                'usage'          => 'amount/money:4',
                'description'    => 'Price amount of the subscription entry.',
                'function'       => 'calcPrice',
                'store'          => true
            ],

            'qty' => [
                'type'           => 'float',
                'description'    => 'Quantity of product.',
                'default'        => 1,
                'visible'        => false
            ],

        ];
    }

    public static function calcIsBillable($self) {
        $result = [];
        $self->read(['subscription_id' => ['is_billable']]);
        foreach ($self as $id => $subscription) {
            $result[$id] = $subscription['subscription_id']['is_billable'];
        }
        return $result;
    }

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['subscription_id' => ['customer_id']]);
        foreach ($self as $id => $subscription) {
            $result[$id] = $subscription['subscription_id']['customer_id'];
        }
        return $result;
    }

    public static function calcProductId($self) {
        $result = [];
        $self->read(['subscription_id' => ['product_id']]);
        foreach ($self as $id => $subscription) {
            $result[$id] = $subscription['subscription_id']['product_id'];
        }
        return $result;
    }

    public static function calcPriceId($self) {
        $result = [];
        $self->read(['subscription_id' => ['price_id']]);
        foreach ($self as $id => $subscription) {
            $result[$id] = $subscription['subscription_id']['price_id'];
        }
        return $result;
    }

    public static function calcPrice($self) {
        $result = [];
        $self->read(['price_id' => ['price']]);
        foreach($self as $id => $line) {
            $result[$id] = $line['price_id']['price'];
        }
        return $result;
    }
}
