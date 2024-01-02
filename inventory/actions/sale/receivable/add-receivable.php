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

$subscriptionEntry = SubscriptionEntry::id($params['id'])
    ->read([
        'id',
        'customer_id',
        'name',
        'product_id',
        'price_id',
        'price',
        'qty',
        'has_receivable'
    ])
    ->first();

if(!$subscriptionEntry) {
    throw new Exception('unknown_subscription_entry', QN_ERROR_UNKNOWN_OBJECT);
}

$receivablesQueue = ReceivablesQueue::search(['customer_id', '=', $subscriptionEntry['customer_id']])
    ->read(['id'])
    ->first();


if(!$receivablesQueue) {
    $receivablesQueue = ReceivablesQueue::create(['customer_id' => $subscriptionEntry['customer_id']])
        ->first();
}

$price = Price::id($subscriptionEntry['price_id'])->read(['id','vat_rate'])->first();

if(!$price) {
    throw new Exception('unknown_price', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable = Receivable::search([
        ['receivables_queue_id', '=', $receivablesQueue['id']],
        ['product_id', '=', $subscriptionEntry['product_id']],
        ['price_id', '=', $price['id']],
        ['status', '=', 'pending'],
    ])
    ->read(['id'])
    ->first();

if(!$receivable) {
    $receivable = Receivable::create([
        'receivables_queue_id' => $receivablesQueue['id'],
        'date'                 => time(),
        'product_id'           => $subscriptionEntry['product_id'],
        'price_id'             => $price['id'],
        'unit_price'           => $subscriptionEntry['price'],
        'vat_rate'             => $price['vat_rate'],
        'qty'                  => $subscriptionEntry['qty'],
        'description'          => 'reference subscription',
    ])
    ->first();

    SubscriptionEntry::id($subscriptionEntry['id'])
        ->update([
            'has_receivable' => true,
            'receivable_id'  => $receivable['id']
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
