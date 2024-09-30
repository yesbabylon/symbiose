<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;
use sale\SaleEntry;

list($params, $providers) = eQual::announce([
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
['context' => $context] = $providers;

$saleEntry = SaleEntry::id($params['id'])->read(['id', 'customer_id'])->first();

if(!$saleEntry) {
    throw new Exception('unknown_sale_entry', EQ_ERROR_UNKNOWN_OBJECT);
}

if(isset($params['receivables_queue_id'])) {
    $receivablesQueue = ReceivablesQueue::id($params['receivables_queue_id'])
        ->read(['customer_id'])
        ->first();

    if(!$receivablesQueue) {
        throw new Exception('unknown_receivables_queue', EQ_ERROR_UNKNOWN_OBJECT);
    }

    if($receivablesQueue['customer_id'] != $saleEntry['customer_id']) {
        throw new Exception('invalid_receivables_queue', EQ_ERROR_UNKNOWN_OBJECT);
    }
}

SaleEntry::id($params['id'])->do('create_receivable');

if(isset($params['receivables_queue_id'])) {
    Receivable::search(['sale_entry_id', '=', $params['id']])
        ->update(['receivables_queue_id' => $params['receivables_queue_id']]);
}

$context->httpResponse()
        ->status(204)
        ->send();
