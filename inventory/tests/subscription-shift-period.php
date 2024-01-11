<?php

use inventory\service\Subscription;

$tests = [
    '0101' => [
        'description' => 'Tests that action shift-period throws if subscription does not exist',
        'return'      => 'integer',
        'expected'    => QN_ERROR_UNKNOWN_OBJECT,
        'test'        => function() {
            $error = 0;
            try {
                // Run action
                eQual::run('do', 'inventory_service_subscription_shift-period', ['id' => '-1']);
            } catch(Exception $e) {
                $error = $e->getCode();
            }

            return $error;
        }
    ],

    '0102' => [
        'description' => 'Tests that action shift-period shifts the subscription period monthly',
        'arrange'     => function() {
            // Create subscription
            $subscription = Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('12 January 2024'),
                'date_to'    => strtotime('12 February 2024'),
                'duration'   => 'monthly',
                'service_id' => 1
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_shift-period',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert'      => function($subscription_id) {
            // Assert that the period is shifted one month
            $subscription = Subscription::id($subscription_id)
                ->read(['date_from', 'date_to'])
                ->first();

            return $subscription['date_from'] === strtotime('12 February 2024')
                && $subscription['date_to'] === strtotime('12 March 2024');
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action shift-period shifts the subscription period yearly',
        'arrange'     => function() {
            // Create subscription
            $subscription = Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('12 January 2023'),
                'date_to'    => strtotime('12 January 2024'),
                'duration'   => 'yearly',
                'service_id' => 1
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_shift-period',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert'      => function($subscription_id) {
            // Assert that the period is shifted one year
            $subscription = Subscription::id($subscription_id)
                ->read(['date_from', 'date_to'])
                ->first();

            return $subscription['date_from'] === strtotime('12 January 2024')
                && $subscription['date_to'] === strtotime('12 January 2025');
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0104' => [
        'description' => 'Tests that action shift-period shifts the subscription period quarterly',
        'arrange'     => function() {
            // Create subscription
            $subscription = Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('12 January 2024'),
                'date_to'    => strtotime('12 April 2024'),
                'duration'   => 'quarterly',
                'service_id' => 1
            ])
                ->read(['id'])
                ->first();

            return $subscription['id'];
        },
        'act'         => function($subscription_id) {
            // Run action
            eQual::run(
                'do',
                'inventory_service_subscription_shift-period',
                ['id' => $subscription_id]
            );

            return $subscription_id;
        },
        'assert'      => function($subscription_id) {
            // Assert that the period is shifted three months
            $subscription = Subscription::id($subscription_id)
                ->read(['date_from', 'date_to'])
                ->first();

            return $subscription['date_from'] === strtotime('12 April 2024')
                && $subscription['date_to'] === strtotime('12 July 2024');
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ]
];
