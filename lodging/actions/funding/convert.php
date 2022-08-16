<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use identity\Partner;
use lodging\sale\booking\Funding;


use lodging\sale\booking\Invoice;
use lodging\sale\booking\InvoiceLine;
use lodging\sale\catalog\Product;
use sale\price\Price;
use core\setting\Setting;


list($params, $providers) = announce([
    'description'   => "Convert given funding to an invoice.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the funding to be converted.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'partner_id' =>  [
            'description'   => 'Identifier of the partner (organisation) to who the invoice must be emitted (can be arbitrary).',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'payment_terms_id' =>  [
            'description'   => 'Identifier of the payment terms to apply.',
            'type'          => 'integer',
            'min'           => 1,
            'default'       => 1
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$partners = Partner::search(['id', '=', $params['partner_id']])->get();

if(!count($partners)) {
    throw new Exception("unknown_partner", QN_ERROR_UNKNOWN_OBJECT);
}

$funding = Funding::id($params['id'])
                    ->read(['type'])
                    ->first();

if(!$funding) {
    // unknonw funding
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if($funding['type'] == 'invoice') {
    // already an invoice
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// convert the installment to an invoice
$orm->call(Funding::getType(), '_convertToInvoice', $params['id']);

// #todo - create scheduled tasks for setting payment_status


$context->httpResponse()
        ->status(204)
        ->send();