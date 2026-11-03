<?php

use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$tests = [
    '0101' => [
        'description' => 'A subscription entry remains associated with the current period when it covers its midpoint after a slight realignment.',
        'arrange'     => function() {
            $suffix = uniqid();
            $date_from = strtotime('2026-01-03');
            $date_to = strtotime('2026-02-02');

            $subscription = Subscription::create([
                    'name'      => "Test current sale entry {$suffix}",
                    'date_from' => $date_from,
                    'date_to'   => $date_to,
                    'duration'  => 'monthly'
                ])
                ->read(['id'])
                ->first(true);

            $subscription_entry = SubscriptionEntry::create([
                    'subscription_id' => $subscription['id'],
                    'date_from'       => $date_from,
                    'date_to'         => $date_to
                ])
                ->read(['id'])
                ->first(true);

            return [
                'subscription_id'       => $subscription['id'],
                'subscription_entry_id' => $subscription_entry['id']
            ];
        },
        'act'         => function($args) {
            Subscription::id($args['subscription_id'])
                ->update(['date_from' => strtotime('2026-01-01')]);

            $overlapping_subscription = Subscription::id($args['subscription_id'])
                ->read(['has_current_sale_entry'])
                ->first(true);

            Subscription::id($args['subscription_id'])
                ->update(['date_from' => strtotime('2026-03-01')]);

            $separate_subscription = Subscription::id($args['subscription_id'])
                ->read(['has_current_sale_entry'])
                ->first(true);

            return array_merge($args, [
                'has_overlapping_entry' => $overlapping_subscription['has_current_sale_entry'] ?? false,
                'has_separate_entry'    => $separate_subscription['has_current_sale_entry'] ?? false
            ]);
        },
        'assert'      => function($args) {
            return $args['has_overlapping_entry']
                && !$args['has_separate_entry'];
        },
        'rollback'    => function($args) {
            SubscriptionEntry::id($args['subscription_entry_id'])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);
        }
    ],
    '0102' => [
        'description' => 'A subscription entry from the previous period is not current when only its boundary overlaps the realigned period.',
        'arrange'     => function() {
            $suffix = uniqid();

            $subscription = Subscription::create([
                    'name'      => "Test previous sale entry {$suffix}",
                    'date_from' => strtotime('2026-02-01'),
                    'date_to'   => strtotime('2026-02-28'),
                    'duration'  => 'monthly'
                ])
                ->read(['id'])
                ->first(true);

            $subscription_entry = SubscriptionEntry::create([
                    'subscription_id' => $subscription['id'],
                    'date_from'       => strtotime('2026-01-03'),
                    'date_to'         => strtotime('2026-02-02')
                ])
                ->read(['id'])
                ->first(true);

            return [
                'subscription_id'       => $subscription['id'],
                'subscription_entry_id' => $subscription_entry['id']
            ];
        },
        'act'         => function($args) {
            $subscription = Subscription::id($args['subscription_id'])
                ->read(['has_current_sale_entry'])
                ->first(true);

            return array_merge($args, [
                'has_current_entry' => $subscription['has_current_sale_entry'] ?? false
            ]);
        },
        'assert'      => function($args) {
            return !$args['has_current_entry'];
        },
        'rollback'    => function($args) {
            SubscriptionEntry::id($args['subscription_entry_id'])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);
        }
    ],
    '0103' => [
        'description' => 'Approving a current sale entry aligns its end date and advances the subscription period.',
        'arrange'     => function() {
            $suffix = uniqid();

            $subscription = Subscription::create([
                    'name'          => "Test approved sale entry {$suffix}",
                    'date_from'     => strtotime('2026-01-01'),
                    'date_to'       => strtotime('2026-01-31'),
                    'duration'      => 'monthly',
                    'is_auto_renew' => false
                ])
                ->read(['id'])
                ->first(true);

            $subscription_entry = SubscriptionEntry::create([
                    'subscription_id' => $subscription['id'],
                    'date_from'       => strtotime('2026-01-02'),
                    'date_to'         => strtotime('2026-02-01')
                ])
                ->read(['id'])
                ->first(true);

            return [
                'subscription_id'       => $subscription['id'],
                'subscription_entry_id' => $subscription_entry['id']
            ];
        },
        'act'         => function($args) {
            SubscriptionEntry::id($args['subscription_entry_id'])
                ->transition('approve');

            $subscription = Subscription::id($args['subscription_id'])
                ->read(['date_from', 'date_to'])
                ->first(true);

            $subscription_entry = SubscriptionEntry::id($args['subscription_entry_id'])
                ->read(['date_to', 'status'])
                ->first(true);

            return array_merge($args, [
                'subscription_date_from' => $subscription['date_from'],
                'subscription_date_to'   => $subscription['date_to'],
                'entry_date_to'          => $subscription_entry['date_to'],
                'entry_status'           => $subscription_entry['status']
            ]);
        },
        'assert'      => function($args) {
            return $args['entry_status'] === 'approved'
                && $args['entry_date_to'] === strtotime('2026-01-31')
                && $args['subscription_date_from'] === strtotime('2026-02-01')
                && $args['subscription_date_to'] === strtotime('2026-02-28');
        },
        'rollback'    => function($args) {
            SubscriptionEntry::id($args['subscription_entry_id'])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);
        }
    ]
];
