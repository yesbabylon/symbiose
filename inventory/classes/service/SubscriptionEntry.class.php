<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

class SubscriptionEntry extends \sale\SaleEntry {

    public static function getColumns() {
        return [

            /**
             * Override SaleEntry columns
             */

            'qty' => [
                'type'           => 'float',
                'description'    => 'Quantity of product.',
                'default'        => 1,
                'visible'        => false
            ],

            'object_class' => [
                'type'           => 'string',
                'description'    => 'Class of the object object_id points to.',
                'default'        => 'inventory\service\Subscription',
                'dependencies'   => ['subscription_id']
            ],

            /**
             * Specific SubscriptionEntry columns
             */

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

        ];
    }

    public static function calcIsBillable($self) {
        $result = [];
        $self->read(['subscription_id' => ['is_billable']]);
        foreach ($self as $id => $subscription_entry) {
            $result[$id] = $subscription_entry['subscription_id']['is_billable'];
        }
        return $result;
    }

    public static function calcCustomerId($self) {
        $result = [];
        $self->read(['subscription_id' => ['customer_id']]);
        foreach ($self as $id => $subscription_entry) {
            $result[$id] = $subscription_entry['subscription_id']['customer_id'];
        }
        return $result;
    }

    public static function calcProductId($self) {
        $result = [];
        $self->read(['subscription_id' => ['product_id']]);
        foreach ($self as $id => $subscription_entry) {
            $result[$id] = $subscription_entry['subscription_id']['product_id'];
        }
        return $result;
    }
}
