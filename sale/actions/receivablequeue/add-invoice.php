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
    'description'   => 'Create invoices for all pending receivables of given queues.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted reports.',
            'type'           => 'one2many',
            'foreign_object' => 'sale\receivable\Receivable',
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

$receivablesQueues = ReceivablesQueue::ids($params['ids'])
    ->read(['id','customer_id' => ['name'],'receivables_ids']);

if(!$receivablesQueues) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

foreach($receivablesQueues as $receivablesQueue) {
    $receivables = Receivable::search([
        ['id', 'in', $receivablesQueue['receivables_ids']],
        ['status', '=', 'pending']
    ])
        ->read([
            'id',
            'name',
            'description',
            'customer_id',
            'product_id',
            'price_id',
            'unit_price',
            'qty',
            'free_qty',
            'discount',
            'total',
            'price'
        ]);

    foreach($receivables as $receivable) {
        $invoice = Invoice::search([
                ['customer_id', '=', $receivable['customer_id']],
                ['status', '=', 'proforma']
            ])
            ->read(['status'])
            ->first();

        if(!$invoice){
            $invoice = Invoice::create([
                'customer_id' => $receivable['customer_id']
            ])->first();
        }

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
        ])->first();

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
