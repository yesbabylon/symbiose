<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use finance\accounting\Invoice;
use sale\receivable\Receivable;
use sale\customer\Customer;

list($params, $providers) = announce([
    'description'   => "Create a invoice.",
    'params'        => [

        'id' =>  [
            'description'       => 'Identifier of the targeted reports.',
            'type'              => 'integer',
            'required'          => true
        ],

        'is_receivables_pending' => [
            'type'              => 'boolean',
            'description'       => 'The receivables be will pending',
            'default'           => true
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

$invoice = Invoice::ids($params['id'])
    ->read([
        'id',
        'status',
        'customer_id'
    ])->first();

if(!$invoice) {
    throw new Exception('unknown_invoice', QN_ERROR_UNKNOWN_OBJECT);
}

$receivables= Receivable::search([
        ['status', "=", "invoiced"],
        ['invoice_id', "=", $invoice['id']],
    ])
    ->read(['id', 'status', 'invoice_id', 'invoice_line_id','receivable'=>'name']);

if(!$receivables) {
    throw new Exception('unknown_receivable', QN_ERROR_UNKNOWN_OBJECT);
}

foreach($receivables as $id => $receivable) {
    if($params['is_receivables_pending']){
        Receivable::ids($receivable['id'])
            ->update([
                'status'               => 'pending',
                'invoice_id'           => null,
                'invoice_line_id'      => null
            ]);
    }
    else {
        Receivable::ids($receivable['id'])
            ->update([
                'status'               => 'cancelled'
            ]);
    }
}

Invoice::ids($invoice['id'])
    ->update([
        'status'      => 'cancelled',
    ]);


$context->httpResponse()
->status(204)
->send();