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

list($params, $providers) = eQual::announce([
    'description'   => "Invoice pending receivables of selected queues.\nSelect an existing invoice or leave empty to create a new one.",
    'help'          => "Create invoice lines from pending receivables of selected queues. Create new invoice if no pending proforma found for customer.",
    'params'        => [
        'id' => [
            'type'           => 'integer',
            'description'    => 'Unique identifier of the targeted receivables queue.',
            'default'        => 0
        ],
        'ids' => [
            'type'           => 'one2many',
            'foreign_object' => 'sale\receivable\ReceivablesQueue',
            'description'    => 'Identifier of the targeted receivables queues.',
            'default'        => []
        ],
        'invoice_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'sale\accounting\invoice\Invoice',
            'description'    => 'If left empty a new invoice proforma will be created.',
            'domain'         => ['status', '=', 'proforma'],
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
['context' => $context] = $providers;

if(empty($params['ids'])) {
    if( !isset($params['id']) || $params['id'] <= 0 ) {
        throw new Exception('object_invalid_id', QN_ERROR_INVALID_PARAM);
    }
    $params['ids'][] = $params['id'];
}

$receivables_queues = ReceivablesQueue::ids($params['ids'])
    ->read(['id', 'customer_id']);

if(!$receivables_queues) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

$default_invoice = null;
if(isset($params['invoice_id'])) {
    $default_invoice = Invoice::search([
            ['id', '=', $params['invoice_id']],
            ['status', '=', 'proforma']
        ])
        ->read(['customer_id'])
        ->first();

    if(!$default_invoice) {
        throw new Exception('unknown_invoice', QN_ERROR_UNKNOWN_OBJECT);
    }
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

    $invoice = null;
    if(!is_null($default_invoice) && $receivables_queue['customer_id'] === $default_invoice['customer_id']) {
        $invoice = $default_invoice;
    }
    else {
        $invoice = Invoice::search([
                ['customer_id', '=', $receivables_queue['customer_id']],
                ['status', '=', 'proforma']
            ])
            ->read(['id'])
            ->first();

        if(!$invoice) {
            $invoice = Invoice::create([
                    'customer_id' => $receivables_queue['customer_id']
                ])
                ->first();
        }
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
                'receivable_id'         => $receivable['id']
            ])
            ->do('reset_invoice_prices')
            ->first();

        Receivable::id($receivable['id'])
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
