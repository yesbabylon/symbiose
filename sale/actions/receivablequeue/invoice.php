<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\accounting\invoice\Invoice;
use sale\accounting\invoice\InvoiceLine;
use sale\accounting\invoice\InvoiceLineGroup;
use sale\receivable\ReceivablesQueue;
use sale\receivable\Receivable;

list($params, $providers) = announce([
    'description'   => 'Invoice pending receivables of selected queues.',
    'help'          => 'Create invoice lines from pending receivables of selected queues. Create new invoice if no pending proforma found for customer.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted receivable queues.',
            'type'           => 'one2many',
            'foreign_object' => 'sale\receivable\ReceivablesQueue',
            'required'       => true
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

$receivables_queues = ReceivablesQueue::ids($params['ids'])
    ->read(['id', 'customer_id']);

if(!$receivables_queues) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

foreach($receivables_queues as $receivables_queue) {
    $receivables = Receivable::search([
        ['receivables_queue_id', '=', $receivables_queue['id']],
        ['status', '=', 'pending']
    ])
        ->read([
            'id',
            'description',
            'customer_id',
            'product_id',
            'price_id',
            'unit_price',
            'vat_rate',
            'qty',
            'free_qty',
            'discount'
        ]);

    $invoice = Invoice::search([
        ['customer_id', '=', $receivables_queue['customer_id']],
        ['status', '=', 'proforma']
    ])
        ->read(['id'])
        ->first();

    if(!$invoice){
        $invoice = Invoice::create([
            'customer_id' => $receivables_queue['customer_id']
        ])
            ->first();
    }

    $invoice_line_group = InvoiceLineGroup::create([
        'name'       => 'Additional Services ('.date('Y-m-d').')',
        'invoice_id' => $invoice['id']
    ])
        ->first();

    foreach($receivables as $receivable) {
        $invoice_line = InvoiceLine::create([
            'description'           => $receivable['description'],
            'invoice_line_group_id' => $invoice_line_group['id'],
            'invoice_id'            => $invoice['id'],
            'product_id'            => $receivable['product_id'],
            'price_id'              => $receivable['price_id'],
            'unit_price'            => $receivable['unit_price'],
            'vat_rate'              => $receivable['vat_rate'],
            'qty'                   => $receivable['qty'],
            'free_qty'              => $receivable['free_qty'],
            'discount'              => $receivable['discount'],
        ])
            ->first();

        Receivable::ids($receivable['id'])
            ->update([
                'invoice_id'      => $invoice['id'],
                'invoice_line_id' => $invoice_line['id'],
                'status'          => 'invoiced'
            ]);
    }
}

$context->httpResponse()
        ->status(204)
        ->send();
