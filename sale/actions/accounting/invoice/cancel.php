<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\accounting\invoice\Invoice;

list($params, $providers) = eQual::announce([
    'description'   => 'Cancel given invoices, can keep or cancel linked receivables.',
    'params'        => [

        'id' =>  [
            'type'              => 'integer',
            'description'       => 'Identifier of the targeted invoice.',
            'required'          => true
        ],

        'ids' =>  [
            'description'       => 'Identifiers of the targeted invoices.',
            'type'              => 'one2many',
            'foreign_object'    => 'sale\accounting\invoice\Invoice',
            'default'           => []
        ],

        'keep_receivables' => [
            'type'              => 'boolean',
            'description'       => 'If true sets receivables back to pending, else sets them to cancelled.',
            'default'           => true
        ]

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

$context = $providers['context'];

if(empty($params['ids'])) {
    if(!isset($params['id']) || $params['id'] <= 0) {
        throw new Exception('invoice_invalid_id', QN_ERROR_INVALID_PARAM);
    }

    $params['ids'][] = $params['id'];
}

$invoices_ids = Invoice::search([
    ['id', 'in', $params['ids']],
    ['status', '=', 'invoice']
])
    ->ids();

if(count($params['ids']) !== count($invoices_ids)) {
    throw new Exception('invoice_invalid_id', QN_ERROR_INVALID_PARAM);
}

Invoice::ids($invoices_ids)
    ->transition(
        $params['keep_receivables'] ? 'cancel-keep-receivables' : 'cancel'
    );

$context->httpResponse()
        ->status(204)
        ->send();
