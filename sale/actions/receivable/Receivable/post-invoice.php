<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\accounting\invoice\Invoice;
use sale\receivable\Receivable;

[$params, $providers] = eQual::announce([
    'description'   => 'Invoice one or more receivables. Fill in for specific invoice or leave empty to create a new one.',
    'help'          => 'A default invoice can be selected, all receivables from that invoice\'s customer will be added to it.',
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

        'invoice_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\accounting\invoice\Invoice',
            'description'       => 'Proforma will be created (leave empty to create a new one).',
            'domain'            => ['status', '=', 'proforma'],
        ],

        'invoice_line_group_name' =>  [
            'description'       => 'Label for grouping on the invoice (leave empty for preset).',
            'type'              => 'string'
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

$self->do('post_invoice', [
        'invoice_id'              => $params['invoice_id'] ?? null,
        'invoice_line_group_name' => $params['invoice_line_group_name'] ?? null
    ]);

$context->httpResponse()
        ->status(204)
        ->send();
