<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\accounting\invoice\Invoice;
use sale\receivable\Receivable;

[$params, $providers] = eQual::announce([
    'description'   => 'Integrate with the customer Service Account.',
    'params'        => [
        'id' =>  [
            'description'       => 'Identifier of targeted receivable.',
            'type'              => 'many2one',
            'foreign_object'    => 'sale\receivable\Receivable',
            'default'           => 0
        ],

        'ids' =>  [
            'description'       => 'Identifiers of targeted receivables.',
            'type'              => 'one2many',
            'foreign_object'    => 'sale\receivable\Receivable',
            'default'           => []
        ],

        'service_account_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\serviceaccount\ServiceAccount',
            'description'       => 'Service Account on which the receivable has been accounted.',
            'ondelete'          => 'null'
        ]

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'self']
]);

/** @var \equal\php\Context $context */
['context' => $context, 'self' => $self] = $providers;

if(empty($params['ids']) && (!isset($params['id']) || $params['id'] <= 0)) {
    throw new Exception('receivable_invalid_id', EQ_ERROR_INVALID_PARAM);
}

$self->do('post_service_account', [
        'service_account_id'    => $params['service_account_id'] ?? null,
    ]);

$context->httpResponse()
        ->status(204)
        ->send();
