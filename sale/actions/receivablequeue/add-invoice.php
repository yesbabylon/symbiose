<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\Invoice;
use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use sale\receivable\ReceivablesQueue;
use sale\receivable\Receivable;

list($params, $providers) = announce([
    'description'   => 'Create invoices for all pending receivables of selected queues.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted reports.',
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
    'providers'     => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

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
            'qty',
            'free_qty',
            'discount'
        ]);

    $invoice = Invoice::search([
        ['customer_id', '=', $receivables_queues['customer_id']],
        ['status', '=', 'proforma']
    ])
        ->read(['status'])
        ->first();

    if(!$invoice){
        $invoice = Invoice::create([
            'customer_id' => $receivables_queues['customer_id']
        ])
            ->first();
    }

    foreach($receivables as $receivable) {
        $invoice_line_group = InvoiceLineGroup::create([
            'name'       => 'Additional Services ('.date('Y-m-d').')',
            'invoice_id' => $invoice['id']
        ])
            ->first();

        $invoiceLine = InvoiceLine::create([
            'description'           => $receivable['description'],
            'invoice_line_group_id' => $invoice_line_group['id'],
            'invoice_id'            => $invoice['id'],
            'product_id'            => $receivable['product_id'],
            'price_id'              => $receivable['price_id'],
            'unit_price'            => $receivable['unit_price'],
            'qty'                   => $receivable['qty'],
            'free_qty'              => $receivable['free_qty'],
            'discount'              => $receivable['discount'],
        ])
            ->first();

        Receivable::ids($receivable['id'])
            ->update([
                'invoice_id'      => $invoice['id'],
                'invoice_line_id' => $invoiceLine['id'],
                'status'          => 'invoiced'
            ]);
    }

}

$context->httpResponse()
        ->status(204)
        ->send();
