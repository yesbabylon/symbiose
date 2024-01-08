<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;

list($params, $providers) = announce([
    'description'   => 'Create invoices for pending receivables from given queues',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted reports.',
            'type'           => 'one2many',
            'foreign_object' => 'sale\receivable\ReceivablesQueue',
            'required'       => true
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$receivables_queues_ids = ReceivablesQueue::ids($params['ids']);

if(count($receivables_queues_ids) !== count($params['ids'])) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

$receivable_ids = Receivable::search([
    ['receivables_queue_id', 'in', $receivables_queues_ids],
    ['status', '=', 'pending']
])
    ->ids();

if (!empty($receivable_ids)) {
    $result = eQual::run(
        'do',
        'sale_receivable_add-invoice',
        ['ids' => $receivable_ids]
    );
}

$context->httpResponse()
        ->body($result)
        ->send();
