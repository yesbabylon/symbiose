<?php

use sale\accounting\invoice\SaleInvoice;
use sale\accounting\invoice\SaleInvoiceLine;
use sale\customer\Customer;
use sale\price\Price;
use sale\price\PriceList;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

$create_fixture = function() {
    $suffix = uniqid();

    $customer = Customer::create([
            'name'                => "Test unpost receivable customer {$suffix}",
            'partner_identity_id' => 0
        ])
        ->read(['id'])
        ->first(true);

    $receivables_queue = ReceivablesQueue::create([
            'customer_id' => $customer['id']
        ])
        ->read(['id'])
        ->first(true);

    $price_list = PriceList::create([
            'name'      => "Test unpost receivable price list {$suffix}",
            'date_from' => strtotime('-1 day'),
            'date_to'   => strtotime('+1 day'),
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

    $subscription = Subscription::create([
            'name'        => "Test unpost receivable subscription {$suffix}",
            'date_from'   => strtotime('2026-09-01'),
            'date_to'     => strtotime('2026-09-30'),
            'customer_id' => $customer['id'],
            'product_id'  => 1,
            'price_id'    => $price['id']
        ])
        ->read(['id'])
        ->first(true);

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
        ->read(['id'])
        ->first(true);

    $invoice = SaleInvoice::create([
            'customer_id' => $customer['id']
        ])
        ->read(['id'])
        ->first(true);

    $invoice_line = SaleInvoiceLine::create([
            'invoice_id'     => $invoice['id'],
            'product_id'     => 1,
            'price_id'       => $price['id'],
            'qty'            => 1,
            'has_receivable' => true,
            'receivable_id'  => $receivable['id']
        ])
        ->read(['id'])
        ->first(true);

    Receivable::id($receivable['id'])
        ->update([
            'status'          => 'settled',
            'invoice_id'      => $invoice['id'],
            'invoice_line_id' => $invoice_line['id']
        ]);

    return [
        'customer_id'           => $customer['id'],
        'receivables_queue_id'  => $receivables_queue['id'],
        'price_list_id'         => $price_list['id'],
        'price_id'              => $price['id'],
        'subscription_id'       => $subscription['id'],
        'subscription_entry_id' => $subscription_entry['id'],
        'receivable_id'         => $receivable['id'],
        'invoice_id'            => $invoice['id'],
        'invoice_line_id'       => $invoice_line['id']
    ];
};

$rollback_fixture = function($args) {
    Receivable::id($args['receivable_id'])
        ->delete(true);

    SaleInvoice::id($args['invoice_id'])
        ->update(['status' => 'proforma'])
        ->delete(true);

    SubscriptionEntry::id($args['subscription_entry_id'])
        ->delete(true);

    Subscription::id($args['subscription_id'])
        ->delete(true);

    ReceivablesQueue::id($args['receivables_queue_id'])
        ->delete(true);

    Price::id($args['price_id'])
        ->delete(true);

    PriceList::id($args['price_list_id'])
        ->delete(true);

    Customer::id($args['customer_id'])
        ->delete(true);
};

$tests = [
    '0101' => [
        'description' => 'Unposting a receivable removes its proforma invoice line and resets the receivable.',
        'arrange'     => $create_fixture,
        'act'         => function($args) {
            Receivable::id($args['receivable_id'])
                ->do('unpost_invoice');

            return $args;
        },
        'assert'      => function($args) {
            $receivable = Receivable::id($args['receivable_id'])
                ->read(['status', 'invoice_id', 'invoice_line_id'])
                ->first(true);

            $invoice_line = SaleInvoiceLine::id($args['invoice_line_id'])
                ->read(['id'])
                ->first(true);

            return $receivable['status'] === 'open'
                && is_null($receivable['invoice_id'])
                && is_null($receivable['invoice_line_id'])
                && !$invoice_line;
        },
        'rollback'    => $rollback_fixture
    ],
    '0102' => [
        'description' => 'A receivable cannot be removed from an emitted invoice.',
        'arrange'     => function() use($create_fixture) {
            $args = $create_fixture();

            SaleInvoice::id($args['invoice_id'])
                ->update(['status' => 'posted']);

            return $args;
        },
        'act'         => function($args) {
            $args['action_rejected'] = false;

            try {
                Receivable::id($args['receivable_id'])
                    ->do('unpost_invoice');
            }
            catch(Exception $e) {
                $args['action_rejected'] = true;
            }

            return $args;
        },
        'assert'      => function($args) {
            $receivable = Receivable::id($args['receivable_id'])
                ->read(['status', 'invoice_id', 'invoice_line_id'])
                ->first(true);

            $invoice_line = SaleInvoiceLine::id($args['invoice_line_id'])
                ->read(['id'])
                ->first(true);

            return $args['action_rejected']
                && $receivable['status'] === 'settled'
                && $receivable['invoice_id'] === $args['invoice_id']
                && $receivable['invoice_line_id'] === $args['invoice_line_id']
                && isset($invoice_line['id']);
        },
        'rollback'    => $rollback_fixture
    ]
];
