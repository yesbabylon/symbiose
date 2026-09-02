<?php

use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$tests = [
    '0101' => [
        'description' => 'Subscription entry name identifies its subscription and period and stays synchronized.',
        'arrange'     => function() {
            $suffix = uniqid();
            $date_from = strtotime('2026-09-01');
            $date_to = strtotime('2026-09-30');
            $subscription_name = "Test subscription {$suffix}";

            $subscription = Subscription::create([
                    'name'      => $subscription_name,
                    'date_from' => $date_from,
                    'date_to'   => $date_to
                ])
                ->read(['id'])
                ->first(true);

            $subscription_entry = SubscriptionEntry::create([
                    'subscription_id' => $subscription['id'],
                    'date_from'       => $date_from,
                    'date_to'         => $date_to
                ])
                ->read(['id', 'name'])
                ->first(true);

            return [
                'subscription_id'         => $subscription['id'],
                'subscription_entry_id'   => $subscription_entry['id'],
                'subscription_name'       => $subscription_name,
                'subscription_entry_name' => $subscription_entry['name']
            ];
        },
        'act'         => function($args) {
            $updated_date_to = strtotime('2026-10-31');
            $updated_subscription_name = $args['subscription_name'].' updated';

            SubscriptionEntry::id($args['subscription_entry_id'])
                ->update(['date_to' => $updated_date_to]);

            $name_after_period_update = SubscriptionEntry::id($args['subscription_entry_id'])
                ->read(['name'])
                ->first(true)['name'];

            Subscription::id($args['subscription_id'])
                ->update(['name' => $updated_subscription_name]);

            $name_after_subscription_update = SubscriptionEntry::id($args['subscription_entry_id'])
                ->read(['name'])
                ->first(true)['name'];

            return array_merge($args, [
                'updated_subscription_name'       => $updated_subscription_name,
                'name_after_period_update'        => $name_after_period_update,
                'name_after_subscription_update'  => $name_after_subscription_update
            ]);
        },
        'assert'      => function($args) {
            return $args['subscription_entry_name'] === $args['subscription_name'].' [2026-09-01 - 2026-09-30]'
                && $args['name_after_period_update'] === $args['subscription_name'].' [2026-09-01 - 2026-10-31]'
                && $args['name_after_subscription_update'] === $args['updated_subscription_name'].' [2026-09-01 - 2026-10-31]';
        },
        'rollback'    => function($args) {
            SubscriptionEntry::id($args['subscription_entry_id'])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);
        }
    ]
];
