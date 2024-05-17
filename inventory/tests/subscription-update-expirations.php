<?php

use inventory\service\Subscription;

$tests = [
    '0101' => [
        'description' => 'Tests that action update-expirations updates when subscription should be expired',
        'arrange'     => function() {
            // Create subscription that should have is_expired to true
            Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('-1 month -1 day'),
                'date_to'    => strtotime('-1 day'),
                'duration'   => 'monthly',
                'is_expired' => false,
                'service_id' => 1
            ])->first();
        },
        'act'         => function() {
            // Run action
            eQual::run('do', 'inventory_service_bulk-expirations');
        },
        'assert'      => function() {
            // Assert that subscription is now expired
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['is_expired'])
                ->first();

            return $subscription['is_expired'] === true;
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0102' => [
        'description' => 'Tests that action update-expirations does not update when subscription should not be expired',
        'arrange'     => function() {
            // Create subscription that should have is_expired to false
            Subscription::create([
                'name'       => 'Test subscription',
                'date_from'  => strtotime('-1 month'),
                'date_to'    => time(),
                'duration'   => 'monthly',
                'is_expired' => false,
                'service_id' => 1
            ])->first();
        },
        'act'         => function() {
            // Run action
            eQual::run('do', 'inventory_service_bulk-expirations');
        },
        'assert'      => function() {
            // Assert that subscription is still not expired
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['is_expired'])
                ->first();

            return $subscription['is_expired'] === false;
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0103' => [
        'description' => 'Tests that action update-expirations updates when subscription should be upcoming expiry',
        'arrange'     => function() {
            // Create subscription that should have has_upcoming_expiry to true
            Subscription::create([
                'name'                => 'Test subscription',
                'date_from'           => strtotime('-15 day'),
                'date_to'             => strtotime('+15 day'),
                'duration'            => 'monthly',
                'has_upcoming_expiry' => false,
                'service_id'          => 1
            ])->first();
        },
        'act'         => function() {
            // Run action
            eQual::run('do', 'inventory_service_bulk-expirations');
        },
        'assert'      => function() {
            // Assert that subscription is now expired
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['has_upcoming_expiry'])
                ->first();

            return $subscription['has_upcoming_expiry'] === true;
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],

    '0104' => [
        'description' => 'Tests that action update-expirations does not update when subscription should not be upcoming expiry',
        'arrange'     => function() {
            // Create subscription that should have has_upcoming_expiry to false
            Subscription::create([
                'name'                => 'Test subscription',
                'date_from'           => strtotime('-8 months'),
                'date_to'             => strtotime('+4 months'),
                'duration'            => 'yearly',
                'has_upcoming_expiry' => false,
                'service_id'          => 1
            ])->first();
        },
        'act'         => function() {
            // Run action
            eQual::run('do', 'inventory_service_bulk-expirations');
        },
        'assert'      => function() {
            // Assert that subscription is still not upcoming expiry
            $subscription = Subscription::search(['name', '=', 'Test subscription'])
                ->read(['has_upcoming_expiry'])
                ->first();

            return $subscription['has_upcoming_expiry'] === false;
        },
        'rollback'    => function() {
            // Remove test subscription
            Subscription::search(['name', '=', 'Test subscription'])
                ->delete(true);
        }
    ],
];
