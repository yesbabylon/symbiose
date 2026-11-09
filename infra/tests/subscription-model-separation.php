<?php

use business\subscription\SubscriptionAbstract;
use infra\service\Subscription;
use sale\subscription\Subscription as SaleSubscription;

$tests = [
    '0101' => [
        'description' => 'Service and commercial subscriptions are sibling specializations of the business abstraction.',
        'return'      => 'boolean',
        'expected'    => true,
        'test'        => function() {
            $service_subscription = new ReflectionClass(Subscription::class);
            $sale_subscription = new ReflectionClass(SaleSubscription::class);

            return $service_subscription->isSubclassOf(SubscriptionAbstract::class)
                && $sale_subscription->isSubclassOf(SubscriptionAbstract::class)
                && !$service_subscription->isSubclassOf(SaleSubscription::class)
                && !$sale_subscription->isSubclassOf(Subscription::class);
        }
    ],
    '0102' => [
        'description' => 'Service and commercial subscriptions expose reciprocal readonly links.',
        'return'      => 'boolean',
        'expected'    => true,
        'test'        => function() {
            $service_columns = Subscription::getColumns();
            $sale_columns = SaleSubscription::getColumns();

            return isset($service_columns['sale_subscription_id'], $sale_columns['infra_subscription_id'])
                && $service_columns['sale_subscription_id']['type'] === 'many2one'
                && $service_columns['sale_subscription_id']['foreign_object'] === SaleSubscription::class
                && $service_columns['sale_subscription_id']['readonly'] === true
                && $sale_columns['infra_subscription_id']['type'] === 'many2one'
                && $sale_columns['infra_subscription_id']['foreign_object'] === Subscription::class
                && $sale_columns['infra_subscription_id']['readonly'] === true;
        }
    ],
    '0103' => [
        'description' => 'Each side of the optional subscription link is unique.',
        'return'      => 'boolean',
        'expected'    => true,
        'test'        => function() {
            $service_subscription = new Subscription();
            $sale_subscription = new SaleSubscription();

            return in_array(['sale_subscription_id'], $service_subscription->getUniques(), true)
                && in_array(['infra_subscription_id'], $sale_subscription->getUniques(), true);
        }
    ]
];
