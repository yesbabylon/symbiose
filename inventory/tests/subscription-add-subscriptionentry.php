<?php

use inventory\Product;
use inventory\service\Service;
use inventory\service\Subscription;
use inventory\service\SubscriptionEntry;
use sale\customer\Customer;

$tests = [
    '0101' => [
        'description' => 'Tests that action add-subscriptionentry throws if subscription does not exist',
        'return'      => 'integer',
        'expected'    => QN_ERROR_UNKNOWN_OBJECT,
        'test'        => function() {
            $error = 0;
            try {
                // Run action
                eQual::run('do', 'inventory_service_subscription_add-subscriptionentry', ['id' => '-1']);
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        }
    ],

    '0102' => [
        'description' => 'Tests that action add-subscriptionentry throws if subscription is internal',
        'arrange'     => function() {
            // Create service and subscription
            $service = Service::create([
                'name'        => 'Test service',
                'product_id'  => 0,
                'customer_id' => 0,
                'is_internal' => true,
            ])
                ->read(['id'])
                ->first();

            $subscription = Subscription::create([
                'name'        => 'Test subscription',
                'date_from'   => strtotime('-1 month -1 day'),
                'date_to'     => strtotime('-1 day'),
                'is_internal' => true,
                'service_id'  => $service['id']
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            $error = 0;
            try {
                // Run action
                eQual::run(
                    'do',
                    'inventory_service_subscription_add-subscriptionentry',
                    ['id' => $subscription_id]
                );
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        },
        'assert'      => function($error) {
            return QN_ERROR_NOT_ALLOWED === $error;
        },
        'rollback'    => function() {
            // Remove service and subscription
            Service::search(['name', '=', 'Test service'])
                ->delete(true);

            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action add-subscriptionentry throws if subscription is not linked to a customer',
        'arrange'     => function() {
            // Create product, service and subscription
            $product = Product::create([
                'name'        => 'Test product',
                'is_internal' => false
            ])
                ->read(['id'])
                ->first();

            $service = Service::create([
                'name'        => 'Test service',
                'product_id'  => $product['id'],
                'is_internal' => false
            ])
                ->read(['id'])
                ->first();

            $subscription = Subscription::create([
                'name'        => 'Test subscription',
                'date_from'   => strtotime('-1 month -1 day'),
                'date_to'     => strtotime('-1 day'),
                'is_internal' => false,
                'service_id'  => $service
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            $error = 0;
            try {
                // Run action
                eQual::run(
                    'do',
                    'inventory_service_subscription_add-subscriptionentry',
                    ['id' => $subscription_id]
                );
            } catch (Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        },
        'assert'      => function($error) {
            return QN_ERROR_NOT_ALLOWED === $error;
        },
        'rollback'    => function() {
            // Remove product, service and subscription
            Product::search(['name', '=', 'Test product'])
                ->delete(true);

            Service::search(['name', '=', 'Test service'])
                ->delete(true);

            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0104' => [
        'description' => 'Tests that action add-subscriptionentry does not create the entry if it already exist',
        'arrange'     => function() {
            // Create customer, subscription and its entry
            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $subscription = Subscription::create([
                'name'        => 'Test subscription',
                'date_from'   => strtotime('-1 month -1 day'),
                'date_to'     => strtotime('-1 day'),
                'price_id'    => 1,
                'price'       => 100,
                'service_id'  => 1,
                'is_internal' => false,
                'customer_id' => $customer['id']
            ])
                ->read(['id'])
                ->first();

            SubscriptionEntry::create([
                'subscription_id' => $subscription['id'],
                'date_from'       => strtotime('-1 month -1 day'),
                'date_to'         => strtotime('-1 day'),
            ]);

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_add-subscriptionentry',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert'      => function($subscription_id) {
            // Assert that there is still only 1 entry linked to the subscription
            $subscription_entries_ids = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])->ids();

            return count($subscription_entries_ids) === 1;
        },
        'rollback'    => function() {
            // Remove customer, subscription and subscription entry
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['id', 'customer_id'])
                ->first();

            Customer::id($subscription['customer_id'])
                ->delete(true);

            Subscription::id($subscription['id'])
                ->delete(true);

            SubscriptionEntry::search(['subscription_id', '=', $subscription['id']])
                ->delete(true);
        }
    ],

    '0105' => [
        'description' => 'Tests that action add-subscriptionentry creates the entry if it does not already exist',
        'arrange'     => function() {
            // Create product, customer and subscription without its entry
            $customer = Customer::create([
                'name'                => 'Test customer',
                'partner_identity_id' => 0
            ])
                ->read(['id'])
                ->first();

            $product = Product::create([
                'name'        => 'Test product',
                'is_internal' => false
            ])
                ->read(['id'])
                ->first();

            $subscription = Subscription::create([
                'name'        => 'Test subscription',
                'date_from'   => strtotime('-1 month -1 day'),
                'date_to'     => strtotime('-1 day'),
                'price_id'    => 1,
                'price'       => 100,
                'service_id'  => 0,
                'is_internal' => false,
                'customer_id' => $customer['id'],
                'product_id'  => $product['id'],
                'is_billable' => true
            ])
                ->read(['id'])
                ->first();

            return [$subscription['id'], $customer['id'], $product['id']];
        },
        'act'         => function($args) {
            list($subscription_id) = $args;

            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_add-subscriptionentry',
                ['id' => $subscription_id]
            );

            return $args;
        },
        'assert'      => function($args) {
            list($subscription_id, $customer_id, $product_id) = $args;

            // Assert that there is now 1 entry linked to the subscription and that the data matches
            $subscription_entries_ids = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])->ids();

            $subscription_entry = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])
                ->read(['date_to', 'date_from', 'price_id', 'unit_price', 'is_billable', 'customer_id', 'product_id'])
                ->first();

            return count($subscription_entries_ids) === 1
                && $subscription_entry['date_from'] === strtotime('-1 month -1 day')
                && $subscription_entry['date_to'] === strtotime('-1 day')
                && $subscription_entry['price_id'] === 1
                && $subscription_entry['unit_price'] === 100
                && $subscription_entry['is_billable'] === true
                && $subscription_entry['customer_id'] === $customer_id
                && $subscription_entry['product_id'] === $product_id;
        },
        'rollback'    => function() {
            // Remove product, customer, subscription and subscription entry
            Product::search(['name', '=', 'Test product'])
                ->delete(true);

            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['id', 'customer_id'])
                ->first();

            Customer::id($subscription['customer_id'])
                ->delete(true);

            Subscription::id($subscription['id'])
                ->delete(true);

            SubscriptionEntry::search(['subscription_id', '=', $subscription['id']])
                ->delete(true);
        }
    ]
];
