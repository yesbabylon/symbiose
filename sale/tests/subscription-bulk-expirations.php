<?php

use core\Task;
use sale\customer\Customer;
use sale\price\Price;
use sale\price\PriceList;
use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$tests = [
    '0101' => [
        'description' => 'Tests that bulk-expirations renews expired auto-renew subscriptions with an existing current entry',
        'arrange'     => function() {
            $suffix = uniqid();

            $customer = Customer::create([
                    'name'                => "Test renew customer {$suffix}",
                    'partner_identity_id' => 0
                ])
                ->read(['id'])
                ->first(true);

            $price_list = PriceList::create([
                    'name'      => "Test renew price list {$suffix}",
                    'date_from' => strtotime('-5 years'),
                    'date_to'   => strtotime('+5 years'),
                    'status'    => 'published'
                ])
                ->read(['id'])
                ->first(true);

            $price = Price::create([
                    'price'         => 100.0,
                    'price_type'    => 'direct',
                    'price_list_id' => $price_list['id'],
                    'product_id'    => 1
                ])
                ->read(['id'])
                ->first(true);

            $date_from = strtotime('-2 months');
            $date_to = strtotime('-1 month');

            $subscription = Subscription::create([
                    'name'          => "Test auto renew subscription {$suffix}",
                    'date_from'     => $date_from,
                    'date_to'       => $date_to,
                    'duration'      => 'monthly',
                    'is_auto_renew' => true,
                    'is_expired'    => true,
                    'customer_id'   => $customer['id'],
                    'product_id'    => 1,
                    'price_id'      => $price['id'],
                    'price'         => 100.0,
                    'is_billable'   => true
                ])
                ->read(['id'])
                ->first(true);

            SubscriptionEntry::create([
                'subscription_id' => $subscription['id'],
                'date_from'       => $date_from,
                'date_to'         => $date_to
            ]);

            return [
                'customer_id'     => $customer['id'],
                'price_list_id'   => $price_list['id'],
                'price_id'        => $price['id'],
                'subscription_id' => $subscription['id'],
                'date_from'       => $date_from
            ];
        },
        'act'         => function($args) {
            eQual::run('do', 'sale_subscription_bulk-expirations', ['ids' => [$args['subscription_id']]]);

            return $args;
        },
        'assert'      => function($args) {
            $subscription_entries_ids = SubscriptionEntry::search(['subscription_id', '=', $args['subscription_id']])
                ->ids();

            $subscription = Subscription::id($args['subscription_id'])
                ->read(['date_from'])
                ->first(true);

            return count($subscription_entries_ids) > 1
                && $subscription['date_from'] > $args['date_from'];
        },
        'rollback'    => function($args) {
            SubscriptionEntry::search(['subscription_id', '=', $args['subscription_id']])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);

            Price::id($args['price_id'])
                ->delete(true);

            PriceList::id($args['price_list_id'])
                ->delete(true);

            Customer::id($args['customer_id'])
                ->delete(true);

            Task::search(['name', '=', "subscription.{$args['subscription_id']}.renew"])
                ->delete(true);

            Task::search(['name', '=', "subscription.{$args['subscription_id']}.create.subscriptionEntry"])
                ->delete(true);
        }
    ]
];
