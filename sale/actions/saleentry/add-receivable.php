<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\price\Price;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\SaleEntry;

list($params, $providers) = announce([
    'description'   => 'Create a receivable from a sale entry.',
    'params'        => [
        'id' =>  [
            'description'   => 'ID of the sale entry.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$sale_entry = SaleEntry::id($params['id'])
    ->read([
        'id',
        'object_class',
        'customer_id',
        'product_id',
        'price_id',
        'unit_price',
        'qty',
        'has_receivable'
    ])
    ->first();

if(!$sale_entry) {
    throw new Exception('unknown_sale_entry', QN_ERROR_UNKNOWN_OBJECT);
}

$receivables_queue = ReceivablesQueue::search(
    ['customer_id', '=', $sale_entry['customer_id']]
)
    ->read(['id'])
    ->first();

if(!$receivables_queue) {
    $receivables_queue = ReceivablesQueue::create([
        'customer_id' => $sale_entry['customer_id']
    ])
        ->first();
}

$price = Price::id($sale_entry['price_id'])
    ->read(['id', 'price', 'vat_rate'])
    ->first();

if(!$price) {
    throw new Exception('unknown_price', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable = Receivable::search([
    ['receivables_queue_id', '=', $receivables_queue['id']],
    ['product_id', '=', $sale_entry['product_id']],
    ['price_id', '=', $price['id']],
    ['status', '=', 'pending']
])
    ->read(['id'])
    ->first();

if(!$receivable) {
    $objectName = 'Sale';
    if(!is_null($sale_entry['object_class'])) {
        $objectName = array_reverse(
            explode('\\', $sale_entry['object_class'])
        )[0];
    }

    $receivable = Receivable::create([
        'receivables_queue_id' => $receivables_queue['id'],
        'date'                 => time(),
        'product_id'           => $sale_entry['product_id'],
        'price_id'             => $price['id'],
        'unit_price'           => $sale_entry['unit_price'],
        'vat_rate'             => $price['vat_rate'],
        'qty'                  => $sale_entry['qty'],
        'description'          => "Reference $objectName entry product.",
    ])
        ->first();

    SaleEntry::ids($sale_entry['id'])
        ->update([
            'has_receivable' => true,
            'receivable_id'  => $receivable['id']
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
