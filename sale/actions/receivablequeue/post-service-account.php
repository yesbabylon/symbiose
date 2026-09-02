<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\serviceaccount\ServiceAccount;
use sale\receivable\Receivable;
use sale\receivable\ReceivablesQueue;

list($params, $providers) = eQual::announce([
    'description'   => "Post pending time receivables of selected queues to Service Accounts.\nSelect an existing Service Account or leave empty to use each customer's active Service Account.",
    'help'          => "Create Service Account entries from pending time receivables of selected queues.",
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
        'service_account_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'sale\serviceaccount\ServiceAccount',
            'description'    => 'If left empty, the active Service Account of each customer will be used.',
            'domain'         => ['is_active', '=', true]
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
    if(!isset($params['id']) || $params['id'] <= 0) {
        throw new Exception('object_invalid_id', QN_ERROR_INVALID_PARAM);
    }
    $params['ids'][] = $params['id'];
}

$receivables_queues = ReceivablesQueue::ids($params['ids'])
    ->read(['id', 'customer_id']);

if(!$receivables_queues) {
    throw new Exception('unknown_receivables_queue', QN_ERROR_UNKNOWN_OBJECT);
}

$default_service_account = null;
if(isset($params['service_account_id']) && $params['service_account_id'] > 0) {
    $default_service_account = ServiceAccount::id($params['service_account_id'])
        ->read(['id', 'customer_id', 'is_active'])
        ->first();

    if(!$default_service_account) {
        throw new Exception('unknown_service_account', EQ_ERROR_UNKNOWN_OBJECT);
    }

    if(!isset($default_service_account['is_active']) || !$default_service_account['is_active']) {
        throw new Exception('inactive_service_account', EQ_ERROR_INVALID_PARAM);
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

    $service_account_id = null;
    if(
        $default_service_account
        && isset($receivables_queue['customer_id'])
        && isset($default_service_account['customer_id'])
        && (int) $receivables_queue['customer_id'] === (int) $default_service_account['customer_id']
    ) {
        $service_account_id = $default_service_account['id'];
    }

    Receivable::ids($receivables_ids)
        ->do('post_service_account', [
            'service_account_id' => $service_account_id
        ]);
}

$context->httpResponse()
        ->status(204)
        ->send();
