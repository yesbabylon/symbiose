<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\Invoice;
use finance\accounting\InvoiceLine;
use finance\accounting\InvoiceLineGroup;
use sale\receivable\Receivable;

list($params, $providers) = announce([
    'description'   => 'Create an invoice for selected receivables.',
    'params'        => [
        'ids' =>  [
            'description'       => 'Identifier of the targeted reports.',
            'type'              => 'one2many',
            'foreign_object'    => 'sale\receivable\Receivable',
            'required'          => true
        ],

        'invoice_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'finance\accounting\Invoice',
            'description'       => 'If left empty a new invoice proforma will be created.',
            'domain'            => [
                ['customer_id', '=', 'parent.customer_id'],
                ['status', '=', 'proforma'],
            ]
        ],

        'title' =>  [
            'description'       => 'Title of the invoice line Group.',
            'type'              => 'string',
            'default'           => 'Additional Services ('.date('Y-m-d').')',
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

$receivable_first = Receivable::ids($params['ids'])->read(['id', 'customer_id'])->first();

if(empty($params['invoice_id'])) {
    $invoice = Invoice::create([
        'customer_id' => $receivable_first['customer_id']
    ])
        ->first();
}
else {
    $invoice = Invoice::search([
            ['id', '=', $params['invoice_id']],
            ['status', '=', 'proforma']
        ])
        ->read(['status'])
        ->first();

    if(!$invoice) {
        throw new Exception('unknown_invoice', QN_ERROR_UNKNOWN_OBJECT);
    }
}

$invoice_line_group = InvoiceLineGroup::create([
    'name'       => $params['title'],
    'invoice_id' => $invoice['id']
])
    ->first();

$receivables = Receivable::search([
    ['id', 'in', $params['ids']],
    ['status', '=', 'pending']
])
    ->read([
        'id',
        'description',
        'product_id',
        'price_id',
        'unit_price',
        'qty',
        'free_qty',
        'discount'
    ]);

foreach($receivables as $id => $receivable) {
    $invoiceLine = InvoiceLine::create([
            'description'           => $receivable['description'],
            'invoice_line_group_id' => $invoice_line_group['id'],
            'invoice_id'            => $invoice['id'],
            'product_id'            => $receivable['product_id'],
            'price_id'              => $receivable['price_id'],
            'unit_price'            => $receivable['unit_price'],
            'qty'                   => $receivable['qty'],
            'free_qty'              => $receivable['free_qty'],
            'discount'              => $receivable['discount']
        ])
        ->first();

    Receivable::ids($receivable['id'])
        ->update([
            'invoice_id'      => $invoice['id'],
            'invoice_line_id' => $invoiceLine['id'],
            'status'          => 'invoiced'
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
