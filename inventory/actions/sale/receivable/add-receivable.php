<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\price\Price;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use inventory\service\Subscription;

list($params, $providers) = eQual::announce([
    'description'   => "Create a receivable.",
    'params'        => [
        'id' =>  [
            'description'   => 'ID of the subscription.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],

    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$subscription = Subscription::id($params['id'])
                ->read(
                    ['id',
                    'customer_id',
                    'name',
                    'product_id',
                    'price_id',
                    'price',
                    'qty',
                    'has_receivable'
                ])
                ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}
$customer_id = $subscription['customer_id'];

$receivablesQueue = ReceivablesQueue::search(['customer_id', '=', $customer_id])->read(['id'])->first();


if(!$receivablesQueue) {
    $receivablesQueue = ReceivablesQueue::create([
            'customer_id'   => $customer_id
        ])
        ->first();
}

$price =Price::id($subscription['price_id'])->read(['id','vat_rate'])->first();

if(!$price) {
    throw new Exception('unknown_price', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable = Receivable::search([
        ['receivables_queue_id', '=', $receivablesQueue['id']],
        ['product_id', '=', $subscription['product_id']],
        ['price_id', '=', $price['id']],
        ['status', '=', 'proforma'],
    ])
    ->read(['id'])
    ->first();

if(!$receivable) {

    $receivable = Receivable::create([
        'receivables_queue_id'   => $receivablesQueue['id'],
        'date'                   => time(),
        'product_id'             => $subscription['product_id'],
        'price_id'               => $price['id'],
        'unit_price'             => $subscription['price'],
        'vat_rate'               => $price['vat_rate'],
        'qty'                    => $subscription['qty'],
        'description'            => 'reference subscription',
    ])
    ->first();

    Subscription::ids($subscription['id'])
    ->update([
        'has_receivable' => true,
        'receivable_id'  => $receivable['id']
    ]);

}

$context->httpResponse()
        ->status(204)
        ->send();