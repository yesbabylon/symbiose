<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Funding;
use sale\booking\Invoice;


list($params, $providers) = announce([
    'description'   => "Convert given funding into an invoice.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the funding that has to be converted.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'partner_id' =>  [
            'description'   => 'Identifier of the partner (organisation) to who the invoice has to be emitted (can be arbitrary).',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


// #todo - handle 'due_balance', 'credit_balance', 'balanced'
$funding = Funding::id($params['id'])
                    ->read([
                        'booking_id' => [
                            'center_id' => [ 'organisation_id' ]
                        ],
                        'type'
                    ])
                    ->first();

if(!$funding) {
    // unknonw funding
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($funding['type'] == 'invoice') {
    // already an invoice
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

$organisation_id = $funding['booking_id']['center_id']['organisation_id'];

$invoice = Invoice::create([
    'organisation_id'   => $organisation_id, 
    'status'            => 'invoice', 
    'booking_id'        => $funding['booking_id']['id']
])->first();

// covnert funding to 'invoice' type
$funding = Funding::id($params['id'])->update(['type' => 'invoice', 'invoice_id' => $invoice['id']]);


$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();