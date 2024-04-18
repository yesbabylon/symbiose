<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\SaleEntry;
use timetrack\Project;

list($params, $providers) = announce([
    'description'   => 'Create a receivable from a sale entry.',
    'params'        => [
        'id' =>  [
            'type'           => 'integer',
            'description'    => 'Identifier of the targeted sale entry.',
            'required'       => true
        ],

        'receivables_queue_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'sale\receivable\ReceivablesQueue',
            'description'    => 'Customer receivable queue to which the receivable will be added.',
            'domain'         => ['customer_id', '=', 'object.customer_id']
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$getSaleEntry = function($id) {
    $sale_entry = SaleEntry::id($id)
        ->read([
            'id',
            'object_class',
            'customer_id',
            'product_id',
            'price_id' => ['id', 'vat_rate'],
            'unit_price',
            'qty',
            'has_receivable',
            'receivable_name'
        ])
        ->first(true);

    if(!$sale_entry) {
        throw new Exception('unknown_sale_entry', QN_ERROR_UNKNOWN_OBJECT);
    }

    if(!isset($sale_entry['customer_id'], $sale_entry['product_id'], $sale_entry['price_id'])) {
        throw new Exception('sale_entry_missing_params', QN_ERROR_INVALID_PARAM);
    }

    return $sale_entry;
};

$getReceivablesQueue = function($customer_id, $receivable_queue_id) {
    if(isset($receivable_queue_id)) {
        $receivables_queue = ReceivablesQueue::search([
            ['id', '=', $receivable_queue_id],
            ['customer_id', '=', $customer_id]
        ])
            ->read(['id'])
            ->first();

        if(!$receivables_queue) {
            throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
        }
    }
    else {
        $receivables_queue = ReceivablesQueue::search(
            ['customer_id', '=', $customer_id]
        )
            ->read(['id'])
            ->first();

        if(!$receivables_queue) {
            $receivables_queue = ReceivablesQueue::create([
                'customer_id' => $customer_id
            ])
                ->first();
        }
    }

    return $receivables_queue;
};

$sale_entry = $getSaleEntry($params['id']);

$receivables_queue = $getReceivablesQueue(
    $sale_entry['customer_id'],
    $params['receivables_queue_id'] ?? null
);

$receivable = null;
if($sale_entry['object_class'] !== Project::class) {
    $receivable = Receivable::search([
        ['customer_id', '=', $sale_entry['customer_id']],
        ['product_id', '=', $sale_entry['product_id']],
        ['price_id', '=', $sale_entry['price_id']['id']],
        ['status', '=', 'pending']
    ])
        ->read(['id'])
        ->first();
}

if(is_null($receivable)) {
    $object_name = 'Sale';
    if(!is_null($sale_entry['object_class'])) {
        $object_name = array_reverse(
            explode('\\', $sale_entry['object_class'])
        )[0];
    }

    $receivable = Receivable::create([
        'receivables_queue_id' => $receivables_queue['id'],
        'date'                 => time(),
        'product_id'           => $sale_entry['product_id'],
        'price_id'             => $sale_entry['price_id']['id'],
        'unit_price'           => $sale_entry['unit_price'],
        'vat_rate'             => $sale_entry['price_id']['vat_rate'],
        'qty'                  => $sale_entry['qty'],
        'name'                 => $sale_entry['receivable_name'],
        'description'          => "Reference $object_name entry product.",
    ])
        ->first();

    SaleEntry::id($sale_entry['id'])
        ->update([
            'has_receivable' => true,
            'receivable_id'  => $receivable['id']
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
