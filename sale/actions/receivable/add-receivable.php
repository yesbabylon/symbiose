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
    'description'   => 'Create a receivable.',
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
    'providers'     => [ 'context', 'orm' ]
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$saleEntry = SaleEntry::id($params['id'])
                ->read(
                    ['id',
                    'customer_id',
                    'product_id',
                    'price_id',
                    'qty',
                    'has_receivable'
                ])
                ->first();

if(!$saleEntry) {
    throw new Exception('unknown_sale_entry', QN_ERROR_UNKNOWN_OBJECT);
}

$receivablesQueue = ReceivablesQueue::search(
    ['customer_id', '=', $saleEntry['customer_id']
    ])
    ->read(['id'])
    ->first();

if(!$receivablesQueue) {
    $receivablesQueue = ReceivablesQueue::create([
            'customer_id'   => $saleEntry['customer_id']
        ])
        ->first();
}

$price =Price::id($saleEntry['price_id'])
    ->read(['id', 'price', 'vat_rate'])
    ->first();

if(!$price) {
    throw new Exception('unknown_price', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable = Receivable::search([
        ['receivables_queue_id', '=', $receivablesQueue['id']],
        ['product_id', '=', $saleEntry['product_id']],
        ['price_id', '=', $price['id']],
        ['status', '=', 'proforma']
    ])
    ->read(['id'])
    ->first();


if(!$receivable) {

    $receivable = Receivable::create([
        'receivables_queue_id'   => $receivablesQueue['id'],
        'date'                   => time(),
        'product_id'             => $saleEntry['product_id'],
        'price_id'               => $price['id'],
        'unit_price'             => $price['price'],
        'vat_rate'               => $price['vat_rate'],
        'qty'                    => $saleEntry['qty'],
        'description'            => 'reference Sale entry product',
    ])
    ->first();

    SaleEntry::ids($saleEntry['id'])
    ->update([
        'has_receivable' => true,
        'receivable_id'  => $receivable['id']
    ]);

}

$context->httpResponse()
        ->status(204)
        ->send();