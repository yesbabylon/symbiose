<?php

use sale\contract\ContractType;
use sale\catalog\Family;
use sale\catalog\Product;
use sale\catalog\ProductModel;
use sale\customer\Customer;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\serviceaccount\Report;
use sale\serviceaccount\ServiceAccount;
use sale\serviceaccount\ServiceAccountEntry;
use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$create_fixture = function(array $entry_dates = []) {
    $suffix = uniqid();
    $customer = Customer::create([
            'name'                => "Test service account customer {$suffix}",
            'partner_identity_id' => 0
        ])
        ->read(['id'])
        ->first(true);

    $contract_type = ContractType::create([
            'name' => "Test service account type {$suffix}",
            'code' => "SA-{$suffix}"
        ])
        ->read(['id'])
        ->first(true);

    $family = Family::create([
            'name' => "Test service account family {$suffix}"
        ])
        ->read(['id'])
        ->first(true);

    $product_model = ProductModel::create([
            'name'      => "Test service account product model {$suffix}",
            'family_id' => $family['id'],
            'type'      => 'service'
        ])
        ->read(['id'])
        ->first(true);

    $product = Product::create([
            'label'            => "Test service account product {$suffix}",
            'sku'              => "SA-{$suffix}",
            'product_model_id' => $product_model['id']
        ])
        ->read(['id'])
        ->first(true);

    $service_account = ServiceAccount::create([
            'customer_id'          => $customer['id'],
            'contract_type_id'     => $contract_type['id'],
            'date_from'            => strtotime('2026-07-15'),
            'reporting_from'       => strtotime('2026-07-15'),
            'reporting_mode'       => 'send',
            'reporting_frequency'  => 'monthly',
            'is_active'            => true
        ])
        ->read(['id'])
        ->first(true);

    $receivables_queue = ReceivablesQueue::create([
            'customer_id' => $customer['id']
        ])
        ->read(['id'])
        ->first(true);

    $fixture = [
        'customer_id'          => $customer['id'],
        'contract_type_id'     => $contract_type['id'],
        'family_id'            => $family['id'],
        'product_model_id'     => $product_model['id'],
        'product_id'           => $product['id'],
        'service_account_id'   => $service_account['id'],
        'receivables_queue_id' => $receivables_queue['id'],
        'subscription_ids'     => [],
        'subscription_entry_ids' => [],
        'receivable_ids'       => [],
        'entry_ids'            => []
    ];

    foreach($entry_dates as $index => $entry_date) {
        $subscription = Subscription::create([
                'name'        => "Test service account subscription {$suffix}-{$index}",
                'date_from'   => $entry_date,
                'date_to'     => strtotime('+1 month', $entry_date),
                'customer_id' => $customer['id'],
                'product_id'  => $product['id']
            ])
            ->read(['id'])
            ->first(true);

        $subscription_entry = SubscriptionEntry::create([
                'subscription_id' => $subscription['id'],
                'date_from'       => $entry_date,
                'date_to'         => strtotime('+1 month -1 day', $entry_date)
            ])
            ->read(['id'])
            ->first(true);

        $receivable = Receivable::create([
                'receivables_queue_id' => $receivables_queue['id'],
                'origin_object_class'  => SubscriptionEntry::class,
                'origin_object_id'     => $subscription_entry['id'],
                'date'                 => $entry_date
            ])
            ->read(['id'])
            ->first(true);

        Receivable::id($receivable['id'])
            ->do('post_service_account', ['service_account_id' => $service_account['id']]);

        $posted = Receivable::id($receivable['id'])
            ->read(['service_account_entry_id'])
            ->first(true);

        $fixture['subscription_ids'][] = $subscription['id'];
        $fixture['subscription_entry_ids'][] = $subscription_entry['id'];
        $fixture['receivable_ids'][] = $receivable['id'];
        $fixture['entry_ids'][] = $posted['service_account_entry_id'];
    }

    return $fixture;
};

$rollback_fixture = function(array $fixture) {
    if(!empty($fixture['receivable_ids'])) {
        Receivable::ids($fixture['receivable_ids'])->update(['service_account_entry_id' => null]);
    }
    if(!empty($fixture['entry_ids'])) {
        ['db' => $db_connector] = eQual::inject(['db']);
        $db = $db_connector->connect();
        $safe_entry_ids = implode(',', array_map('intval', $fixture['entry_ids']));
        $db->sendQuery("DELETE FROM `sale_serviceaccount_serviceaccountentry` WHERE id IN ({$safe_entry_ids});");
    }

    $report_ids = Report::search([
            ['service_account_id', '=', $fixture['service_account_id']]
        ])
        ->ids();
    if($report_ids) {
        ['db' => $db_connector] = eQual::inject(['db']);
        $db = $db_connector->connect();
        $safe_report_ids = implode(',', array_map('intval', $report_ids));
        $db->sendQuery("DELETE FROM `sale_serviceaccount_report` WHERE id IN ({$safe_report_ids});");
    }

    if(!empty($fixture['receivable_ids'])) {
        Receivable::ids($fixture['receivable_ids'])->delete(true);
    }
    if(!empty($fixture['subscription_entry_ids'])) {
        SubscriptionEntry::ids($fixture['subscription_entry_ids'])->delete(true);
    }
    if(!empty($fixture['subscription_ids'])) {
        Subscription::ids($fixture['subscription_ids'])->delete(true);
    }

    ReceivablesQueue::id($fixture['receivables_queue_id'])->delete(true);
    ServiceAccount::id($fixture['service_account_id'])->delete(true);
    ContractType::id($fixture['contract_type_id'])->delete(true);
    Product::id($fixture['product_id'])->delete(true);
    ProductModel::id($fixture['product_model_id'])->delete(true);
    Family::id($fixture['family_id'])->delete(true);
    Customer::id($fixture['customer_id'])->delete(true);
};

$tests = [
    '0101' => [
        'description' => 'Posting a receivable creates one symmetric and idempotent service account entry.',
        'arrange'     => function() use($create_fixture) {
            return $create_fixture([strtotime('2026-07-20')]);
        },
        'act'         => function($args) {
            Receivable::id($args['receivable_ids'][0])
                ->do('post_service_account', ['service_account_id' => $args['service_account_id']]);
            return $args;
        },
        'assert'      => function($args) {
            $receivable = Receivable::id($args['receivable_ids'][0])
                ->read(['service_account_entry_id'])
                ->first(true);
            $entries = ServiceAccountEntry::search([
                    ['receivable_id', '=', $args['receivable_ids'][0]]
                ])
                ->read(['receivable_id'])
                ->get(true);

            return count($entries) === 1
                && (int) $receivable['service_account_entry_id'] === (int) $args['entry_ids'][0]
                && (int) reset($entries)['receivable_id'] === (int) $args['receivable_ids'][0];
        },
        'rollback'    => $rollback_fixture
    ],
    '0102' => [
        'description' => 'Report generation uses the anniversary period, includes forgotten entries and reuses one draft.',
        'arrange'     => function() use($create_fixture) {
            return $create_fixture([
                strtotime('2026-07-10'),
                strtotime('2026-08-14 12:00:00'),
                strtotime('2026-08-15')
            ]);
        },
        'act'         => function($args) {
            ServiceAccount::id($args['service_account_id'])->do('generate_report');
            ServiceAccount::id($args['service_account_id'])->do('generate_report');
            return $args;
        },
        'assert'      => function($args) {
            $reports = Report::search([
                    ['service_account_id', '=', $args['service_account_id']],
                    ['status', '=', 'pending']
                ])
                ->read(['id', 'date', 'date_from'])
                ->get(true);
            if(count($reports) !== 1) {
                return false;
            }
            $report = reset($reports);
            $entries = [];
            foreach($args['entry_ids'] as $entry_id) {
                $entries[$entry_id] = ServiceAccountEntry::id($entry_id)
                    ->read(['report_id'])
                    ->first(true);
            }

            return date('Y-m-d', $report['date_from']) === '2026-07-15'
                && date('Y-m-d', $report['date']) === '2026-08-14'
                && (int) $entries[$args['entry_ids'][0]]['report_id'] === (int) $report['id']
                && (int) $entries[$args['entry_ids'][1]]['report_id'] === (int) $report['id']
                && empty($entries[$args['entry_ids'][2]]['report_id']);
        },
        'rollback'    => $rollback_fixture
    ],
    '0103' => [
        'description' => 'Estimated and effective balances follow draft generation and irreversible release.',
        'arrange'     => function() use($create_fixture) {
            $fixture = $create_fixture([strtotime('2026-07-20'), strtotime('2026-08-01')]);
            ServiceAccountEntry::id($fixture['entry_ids'][0])->update(['points' => 10.0]);
            ServiceAccountEntry::id($fixture['entry_ids'][1])->update(['points' => 5.0]);
            return $fixture;
        },
        'act'         => function($args) {
            ServiceAccount::id($args['service_account_id'])->do('generate_report');
            $report = Report::search([
                    ['service_account_id', '=', $args['service_account_id']],
                    ['status', '=', 'pending']
                ])
                ->read(['id'])
                ->first(true);

            $before = ServiceAccount::id($args['service_account_id'])
                ->read(['balance_effective', 'balance_estimated'])
                ->first(true);
            Report::id($report['id'])->do('release');

            $args['report_id'] = $report['id'];
            $args['before'] = $before;
            $entry_update_rejected = false;
            $report_reopen_rejected = false;
            try {
                ServiceAccountEntry::id($args['entry_ids'][0])->update(['points' => 99.0]);
            }
            catch(Throwable $throwable) {
                $entry_update_rejected = true;
            }
            try {
                Report::id($report['id'])->update(['status' => 'pending']);
            }
            catch(Throwable $throwable) {
                $report_reopen_rejected = true;
            }
            return array_merge([
                'entry_update_rejected' => $entry_update_rejected,
                'report_reopen_rejected' => $report_reopen_rejected
            ], $args);
        },
        'assert'      => function($args) {
            $account = ServiceAccount::id($args['service_account_id'])
                ->read(['balance_effective', 'balance_estimated'])
                ->first(true);
            $report = Report::id($args['report_id'])->read(['status'])->first(true);
            $entries = [];
            foreach($args['entry_ids'] as $entry_id) {
                $entries[$entry_id] = ServiceAccountEntry::id($entry_id)
                    ->read(['status'])
                    ->first(true);
            }

            return (float) $args['before']['balance_effective'] === 0.0
                && (float) $args['before']['balance_estimated'] === 15.0
                && (float) $account['balance_effective'] === 15.0
                && (float) $account['balance_estimated'] === 15.0
                && $report['status'] === 'released'
                && $entries[$args['entry_ids'][0]]['status'] === 'released'
                && $entries[$args['entry_ids'][1]]['status'] === 'released'
                && $args['entry_update_rejected']
                && $args['report_reopen_rejected'];
        },
        'rollback'    => $rollback_fixture
    ],
    '0104' => [
        'description' => 'Deleting a pending report detaches its entries and leaves them pending.',
        'arrange'     => function() use($create_fixture) {
            return $create_fixture([strtotime('2026-07-20')]);
        },
        'act'         => function($args) {
            ServiceAccount::id($args['service_account_id'])->do('generate_report');
            $report = Report::search([
                    ['service_account_id', '=', $args['service_account_id']],
                    ['status', '=', 'pending']
                ])
                ->read(['id'])
                ->first(true);
            Report::id($report['id'])->delete(true);
            return $args;
        },
        'assert'      => function($args) {
            $entry = ServiceAccountEntry::id($args['entry_ids'][0])
                ->read(['report_id', 'status'])
                ->first(true);
            $account = ServiceAccount::id($args['service_account_id'])
                ->read(['balance_effective', 'balance_estimated'])
                ->first(true);

            return is_null($entry['report_id'])
                && $entry['status'] === 'pending'
                && (float) $account['balance_effective'] === (float) $account['balance_estimated'];
        },
        'rollback'    => $rollback_fixture
    ],
    '0105' => [
        'description' => 'Pending entries can move safely while cross-period assignment and a second draft are rejected.',
        'arrange'     => function() use($create_fixture) {
            return $create_fixture([strtotime('2026-07-20'), strtotime('2026-08-15')]);
        },
        'act'         => function($args) {
            ServiceAccount::id($args['service_account_id'])->do('generate_report');
            $report = Report::search([
                    ['service_account_id', '=', $args['service_account_id']],
                    ['status', '=', 'pending']
                ])
                ->read(['id', 'date'])
                ->first(true);

            ServiceAccountEntry::id($args['entry_ids'][0])->update(['report_id' => null]);
            ServiceAccountEntry::id($args['entry_ids'][0])->update(['report_id' => $report['id']]);

            $future_assignment_rejected = false;
            $second_draft_rejected = false;
            try {
                ServiceAccountEntry::id($args['entry_ids'][1])->update(['report_id' => $report['id']]);
            }
            catch(Throwable $throwable) {
                $future_assignment_rejected = true;
            }
            try {
                Report::create([
                    'service_account_id' => $args['service_account_id'],
                    'date'               => $report['date'],
                    'status'             => 'pending'
                ]);
            }
            catch(Throwable $throwable) {
                $second_draft_rejected = true;
            }

            $args['report_id'] = $report['id'];
            $args['future_assignment_rejected'] = $future_assignment_rejected;
            $args['second_draft_rejected'] = $second_draft_rejected;
            return $args;
        },
        'assert'      => function($args) {
            $included = ServiceAccountEntry::id($args['entry_ids'][0])
                ->read(['report_id', 'status'])
                ->first(true);
            $future = ServiceAccountEntry::id($args['entry_ids'][1])
                ->read(['report_id', 'status'])
                ->first(true);

            return (int) $included['report_id'] === (int) $args['report_id']
                && $included['status'] === 'pending'
                && empty($future['report_id'])
                && $future['status'] === 'pending'
                && $args['future_assignment_rejected']
                && $args['second_draft_rejected'];
        },
        'rollback'    => $rollback_fixture
    ]
];
