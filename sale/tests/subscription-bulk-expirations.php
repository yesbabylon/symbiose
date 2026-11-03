<?php

use core\Task;
use sale\customer\Customer;
use sale\price\Price;
use sale\price\PriceList;
use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$tests = [
    '0101' => [
        'description' => 'Tests that bulk-expirations creates one approved entry and advances an expired auto-renew subscription by one period',
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

            $date_from = strtotime('-3 months');
            $date_to = strtotime('-2 months');

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

            return [
                'customer_id'     => $customer['id'],
                'price_list_id'   => $price_list['id'],
                'price_id'        => $price['id'],
                'subscription_id' => $subscription['id'],
                'date_from'       => $date_from,
                'date_to'         => $date_to
            ];
        },
        'act'         => function($args) {
            eQual::run('do', 'sale_subscription_bulk-expirations', ['ids' => [$args['subscription_id']]]);

            $subscription_entries = SubscriptionEntry::search(['subscription_id', '=', $args['subscription_id']])
                ->read(['status', 'date_from', 'date_to']);

            $subscription = Subscription::id($args['subscription_id'])
                ->read(['date_from', 'date_to'])
                ->first(true);

            return array_merge($args, [
                'subscription_entries'  => $subscription_entries->toArray(),
                'current_date_from'     => $subscription['date_from'],
                'current_date_to'       => $subscription['date_to']
            ]);
        },
        'assert'      => function($args) {
            return count($args['subscription_entries']) === 1
                && $args['subscription_entries'][0]['status'] === 'approved'
                && $args['subscription_entries'][0]['date_to'] === $args['date_to']
                && $args['current_date_from'] === strtotime('+1 day', $args['date_to']);
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
