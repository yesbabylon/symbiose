<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2024
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\service;

use sale\subscription\SubscriptionEntry as SaleSubscriptionEntry;

class SubscriptionEntry extends SaleSubscriptionEntry {

    public static function getColumns(): array {
        return [

            /**
             * Override Sale SubscriptionEntry columns
             */

            'object_class' => [
                'type'           => 'string',
                'description'    => 'Class of the object object_id points to.',
                'default'        => 'inventory\service\Subscription',
                'dependents'     => ['subscription_id']
            ],

            'subscription_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'inventory\service\Subscription',
                'description'    => 'Identifier of the subscription the subscription entry originates from.',
                'store'          => true,
                'function'       => 'calcSubscriptionId',
                'dependents'     => ['product_id', 'customer_id', 'service_provider']
            ],

            /**
             * Specific Inventory SubscriptionEntry columns
             */

            'has_external_provider' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The subscriptionEntry has external provider.',
                'store'             => true,
                'instance'          => true,
                'function'          => 'calcHasExternalProvider'
            ],

            'service_provider_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'inventory\service\ServiceProvider',
                'description'    => 'The service provider to which the subscription belongs.',
                'store'          => true,
                'instance'       => true,
                'function'       => 'calcServiceProviderId'
            ]

        ];
    }

    public static function calcHasExternalProvider($self): array {
        return self::calcFromSubscription($self, 'has_external_provider');
    }

    public static function calcServiceProviderId($self): array {
        return self::calcFromSubscription($self, 'service_provider_id');
    }
}
