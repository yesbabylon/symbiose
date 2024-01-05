<?php

use inventory\service\Subscription;
use inventory\service\SubscriptionEntry;

$tests = [
    '0101' => [
        'description' => 'Tests that action add-subscriptionentry throws if subscription does not exist',
        'return' =>  'integer',
        'expected' => QN_ERROR_UNKNOWN_OBJECT,
        'test' => function() {
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
        'description' => 'Tests that action add-subscriptionentry does not create the entry if it already exist',
        'arrange' => function() {
            // Create subscription and its entry
            $subscription = Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('-1 month -1 day'),
                'date_to'    => strtotime('-1 day'),
                'price_id'   => 1,
                'price'      => 100,
                'service_id' => 1
            ])
                ->read(['id'])
                ->first();

            SubscriptionEntry::create([
                'subscription_id' => $subscription['id'],
                'date_from'  => strtotime('-1 month -1 day'),
                'date_to'    => strtotime('-1 day'),
            ]);

            return $subscription['id'];
        },
        'act' => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_add-subscriptionentry',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert' => function($subscription_id) {
           // Assert that there is still only 1 entry linked to the subscription
            $subscription_entries_ids = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])->ids();

            return count($subscription_entries_ids) === 1;
        },
        'rollback' => function() {
            // Remove subscription and subscription entry
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['id'])
                ->first();

            Subscription::search(['id', '=', $subscription['id']])
                ->delete(true);

            SubscriptionEntry::search(['subscription_id', '=', $subscription['id']])
                ->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action add-subscriptionentry creates the entry if it does not already exist',
        'arrange' => function() {
            // Create subscription without its entry
            $subscription = Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('-1 month -1 day'),
                'date_to'    => strtotime('-1 day'),
                'price_id'   => 1,
                'price'      => 100,
                'service_id' => 1
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act' => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_add-subscriptionentry',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert' => function($subscription_id) {
            // Assert that there is now 1 entry linked to the subscription and that the data matches
            $subscription_entries_ids = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])->ids();

            $subscription_entry = SubscriptionEntry::search(['subscription_id', '=', $subscription_id])
                ->read(['date_to', 'date_from', 'price_id', 'price'])
                ->first();

            return count($subscription_entries_ids) === 1
                && $subscription_entry['date_from'] === strtotime('-1 month -1 day')
                && $subscription_entry['date_to'] === strtotime('-1 day')
                && $subscription_entry['price_id'] === 1
                && $subscription_entry['price'] === 100;
        },
        'rollback' => function() {
            // Remove subscription and subscription entry
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['id'])
                ->first();

            Subscription::search(['id', '=', $subscription['id']])
                ->delete(true);

            SubscriptionEntry::search(['subscription_id', '=', $subscription['id']])
                ->delete(true);
        }
    ]
];
