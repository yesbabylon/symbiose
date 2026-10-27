<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\service;

use sale\subscription\SubscriptionEntry as SaleSubscriptionEntry;

class SubscriptionEntry extends SaleSubscriptionEntry {

    public static function getDescription() {
        return 'Subscription entries describe the process of invoicing the client for the service.
        They are associated with both the customer and the service provider.';
    }

    public static function getColumns(): array {
        return [

            /**
             * Override Sale SubscriptionEntry columns
             */

            'invoice_group' => [
                'type'           => 'string',
                'description'    => 'Optional group name used when creating invoices.'
            ],

            'subscription_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'infra\service\Subscription',
                'description'    => 'Subscription from which the entry originates.',
                'dependents'     => ['product_id', 'customer_id', 'service_provider']
            ],

            /**
             * Specific Infrastructure SubscriptionEntry columns
             */

            'has_external_provider' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'The subscriptionEntry has external provider.',
                'store'             => true,
                'instant'           => true,
                'relation'          => ['subscription_id' => 'has_external_provider']
            ],

            'service_provider_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'inventory\service\ServiceProvider',
                'description'    => 'The service provider to which the subscription belongs.',
                'store'          => true,
                'instant'        => true,
                'relation'       => ['subscription_id' => 'service_provider_id']
            ]

        ];
    }
}
