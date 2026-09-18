<?php

use core\setting\Setting;
use sale\customer\Customer;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;
use timetrack\Project;
use timetrack\TimeEntry;

$tests = [
    '0101' => [
        'description' => 'Receivable name uses the concrete subscription entry name.',
        'arrange'     => function() {
            $suffix = uniqid();
            $subscription_name = "Test receivable subscription {$suffix}";

            $customer = Customer::create([
                    'name'                => "Test receivable customer {$suffix}",
                    'partner_identity_id' => 0
                ])
                ->read(['id'])
                ->first(true);

            $receivables_queue = ReceivablesQueue::create([
                    'customer_id' => $customer['id']
                ])
                ->read(['id'])
                ->first(true);

            $subscription = Subscription::create([
                    'name'        => $subscription_name,
                    'date_from'   => strtotime('2026-09-01'),
                    'date_to'     => strtotime('2026-09-30'),
                    'customer_id' => $customer['id']
                ])
                ->read(['id'])
                ->first(true);

            // Do not read the entry name: the receivable must resolve the concrete origin itself.
            $subscription_entry = SubscriptionEntry::create([
                    'subscription_id' => $subscription['id'],
                    'date_from'       => strtotime('2026-09-01'),
                    'date_to'         => strtotime('2026-09-30')
                ])
                ->read(['id'])
                ->first(true);

            $receivable = Receivable::create([
                    'receivables_queue_id' => $receivables_queue['id'],
                    'origin_object_class'  => SubscriptionEntry::class,
                    'origin_object_id'     => $subscription_entry['id']
                ])
                ->read(['id', 'name'])
                ->first(true);

            return [
                'customer_id'              => $customer['id'],
                'receivables_queue_id'     => $receivables_queue['id'],
                'subscription_id'          => $subscription['id'],
                'subscription_entry_id'    => $subscription_entry['id'],
                'receivable_id'            => $receivable['id'],
                'receivable_name'          => $receivable['name'],
                'expected_receivable_name' => $subscription_name.' [2026-09-01 - 2026-09-30]'
            ];
        },
        'act'         => function($args) {
            return $args;
        },
        'assert'      => function($args) {
            return $args['receivable_name'] === $args['expected_receivable_name'];
        },
        'rollback'    => function($args) {
            Receivable::id($args['receivable_id'])
                ->delete(true);

            SubscriptionEntry::id($args['subscription_entry_id'])
                ->delete(true);

            Subscription::id($args['subscription_id'])
                ->delete(true);

            ReceivablesQueue::id($args['receivables_queue_id'])
                ->delete(true);

            Customer::id($args['customer_id'])
                ->delete(true);
        }
    ],
    '0102' => [
        'description' => 'Receivable name appends the localized date for time entries.',
        'arrange'     => function() {
            $suffix = uniqid();
            $entry_date = strtotime('2026-09-18 10:00:00');

            $customer = Customer::create([
                    'name'                => "Test time entry customer {$suffix}",
                    'partner_identity_id' => 0
                ])
                ->read(['id'])
                ->first(true);

            $receivables_queue = ReceivablesQueue::create([
                    'customer_id' => $customer['id']
                ])
                ->read(['id'])
                ->first(true);

            $project = Project::create([
                    'name'                 => "Test time entry project {$suffix}",
                    'customer_id'          => $customer['id'],
                    'receivable_queue_id' => $receivables_queue['id']
                ])
                ->read(['id'])
                ->first(true);

            $time_entry = TimeEntry::create([
                    'project_id'  => $project['id'],
                    'date'        => $entry_date,
                    'description' => "Test time entry description {$suffix}",
                    'origin'      => 'project'
                ])
                ->read(['id', 'name'])
                ->first(true);

            $receivable = Receivable::create([
                    'receivables_queue_id' => $receivables_queue['id'],
                    'origin_object_class'  => TimeEntry::class,
                    'origin_object_id'     => $time_entry['id'],
                    'date'                 => $entry_date
                ])
                ->read(['id', 'name'])
                ->first(true);

            $date_format = Setting::get_value('core', 'locale', 'date.format', 'm/d/Y');

            return [
                'customer_id'              => $customer['id'],
                'receivables_queue_id'     => $receivables_queue['id'],
                'project_id'               => $project['id'],
                'time_entry_id'            => $time_entry['id'],
                'receivable_id'            => $receivable['id'],
                'receivable_name'          => $receivable['name'],
                'expected_receivable_name' => $time_entry['name'].' ['.date($date_format, $entry_date).']'
            ];
        },
        'act'         => function($args) {
            return $args;
        },
        'assert'      => function($args) {
            return $args['receivable_name'] === $args['expected_receivable_name'];
        },
        'rollback'    => function($args) {
            Receivable::id($args['receivable_id'])
                ->delete(true);

            TimeEntry::id($args['time_entry_id'])
                ->delete(true);

            Project::id($args['project_id'])
                ->delete(true);

            ReceivablesQueue::id($args['receivables_queue_id'])
                ->delete(true);

            Customer::id($args['customer_id'])
                ->delete(true);
        }
    ]
];
