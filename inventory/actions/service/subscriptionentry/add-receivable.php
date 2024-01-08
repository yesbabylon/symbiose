<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\price\Price;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use inventory\service\SubscriptionEntry;

list($params, $providers) = eQual::announce([
    'description' => 'Create a receivable from a subscription entry.',
    'params'      => [
        'id' =>  [
            'description' => 'ID of the subscription entry.',
            'type'        => 'integer',
            'required'    => true
        ]
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$subscription_entry = SubscriptionEntry::id($params['id'])
    ->read([
        'id',
        'customer_id',
        'product_id',
        'price_id',
        'price',
        'qty',
        'has_receivable'
    ])
    ->first();

if(!$subscription_entry) {
    throw new Exception('unknown_subscription_entry', QN_ERROR_UNKNOWN_OBJECT);
}

$receivables_queue = ReceivablesQueue::search(['customer_id', '=', $subscription_entry['customer_id']])
    ->read(['id'])
    ->first();


if(!$receivables_queue) {
    $receivables_queue = ReceivablesQueue::create(['customer_id' => $subscription_entry['customer_id']])
        ->first();
}

$price = Price::id($subscription_entry['price_id'])->read(['id', 'vat_rate'])->first();

if(!$price) {
    throw new Exception('unknown_price', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable = Receivable::search([
    ['receivables_queue_id', '=', $receivables_queue['id']],
    ['product_id', '=', $subscription_entry['product_id']],
    ['price_id', '=', $price['id']],
    ['status', '=', 'pending'],
    ])
    ->read(['id'])
    ->first();

if(!$receivable) {
    $receivable = Receivable::create([
        'receivables_queue_id' => $receivables_queue['id'],
        'date'                 => time(),
        'product_id'           => $subscription_entry['product_id'],
        'price_id'             => $price['id'],
        'unit_price'           => $subscription_entry['price'],
        'vat_rate'             => $price['vat_rate'],
        'qty'                  => $subscription_entry['qty'],
        'description'          => 'reference subscription',
    ])
    ->first();

    SubscriptionEntry::id($subscription_entry['id'])
        ->update([
            'has_receivable' => true,
            'receivable_id'  => $receivable['id']
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
