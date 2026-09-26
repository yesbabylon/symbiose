<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

[$params, $providers] = eQual::announce([
    'description' => 'Advanced search for accounting operations according to posting date, type, and status.',
    'extends'     => 'core_model_collect',
    'params'      => [
        'entity' => [
            'description' => 'Accounting operation entity to collect.',
            'type'        => 'string',
            'default'     => 'finance\accounting\operation\AccountingOperation'
        ],

        'date_from' => [
            'type'        => 'date',
            'description' => 'Start of the posting date interval.',
            'default'     => null
        ],

        'date_to' => [
            'type'        => 'date',
            'description' => 'End of the posting date interval.',
            'default'     => null
        ],

        'operation_type' => [
            'type'        => 'string',
            'description' => 'Semantic type of the accounting operation.',
            'selection'   => [
                'all',
                'misc',
                'sale_invoice',
                'purchase_invoice',
                'sale_credit_note',
                'purchase_credit_note'
            ],
            'default' => 'all'
        ],

        'status' => [
            'type'        => 'string',
            'description' => 'Current lifecycle status of the accounting operation.',
            'selection'   => ['all', 'pending', 'proforma', 'posted', 'cancelled'],
            'default'     => 'all'
        ]
    ],
    'response' => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context']
]);

/** @var \equal\php\Context $context */
['context' => $context] = $providers;

$domain = $params['domain'];

if(isset($params['date_from']) && $params['date_from'] > 0) {
    $domain = Domain::conditionAdd($domain, ['posting_date', '>=', $params['date_from']]);
}

if(isset($params['date_to']) && $params['date_to'] > 0) {
    $domain = Domain::conditionAdd($domain, ['posting_date', '<=', $params['date_to']]);
}

if(isset($params['operation_type']) && $params['operation_type'] !== 'all') {
    $domain = Domain::conditionAdd($domain, ['operation_type', '=', $params['operation_type']]);
}

if(isset($params['status']) && $params['status'] !== 'all') {
    $domain = Domain::conditionAdd($domain, ['status', '=', $params['status']]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
    ->body($result)
    ->send();
