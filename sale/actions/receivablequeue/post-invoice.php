<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\accounting\invoice\Invoice;
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
    ->read(['id']);

if(!$receivables_queues) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

if(isset($params['invoice_id'])) {
    $invoice = Invoice::search([
            ['id', '=', $params['invoice_id']],
            ['status', '=', 'proforma']
        ])
        ->read(['customer_id'])
        ->first();

    if(!$invoice) {
        throw new Exception('unknown_invoice', QN_ERROR_UNKNOWN_OBJECT);
    }
}

foreach($receivables_queues as $receivables_queue) {
    $receivables_ids = Receivable::search([
            ['receivables_queue_id', '=', $receivables_queue['id']],
            ['status', '=', 'pending']
        ])
        ->ids();

    if(empty($receivables_ids)) {
        continue;
    }

    Receivable::ids($receivables_ids)
        ->do('post_invoice', [
            'invoice_id'              => $params['invoice_id'] ?? null,
            'invoice_line_group_name' => 'Additional Services ('.date('Y-m-d').')'
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
