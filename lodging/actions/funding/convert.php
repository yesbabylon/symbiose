<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use identity\Partner;
use lodging\sale\booking\Funding;
use lodging\sale\booking\Invoice;
use finance\accounting\InvoiceLine;
use lodging\sale\catalog\Product;
use core\setting\Setting;

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
    // 'access' => [
    //     'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)
    //     'users'             => [ROOT_USER_ID],		// list of users ids granted 
    //     'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    // ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$partners = Partner::search(['id', '=', $params['partner_id']])->get();

if(!count($partners)) {
    throw new Exception("unknown_partner", QN_ERROR_UNKNOWN_OBJECT);
}

$funding = Funding::id($params['id'])
                    ->read([
                        'booking_id' => [
                            'center_id' => [ 'organisation_id', 'center_office_id' ]
                        ],
                        'type',
                        'due_amount'
                    ])
                    ->first();

if(!$funding) {
    // unknonw funding
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if($funding['type'] == 'invoice') {
    // already an invoice
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

$organisation_id = $funding['booking_id']['center_id']['organisation_id'];
$center_office_id = $funding['booking_id']['center_id']['center_office_id'];

$invoice = Invoice::create([
    'organisation_id'   => $organisation_id,
    'center_office_id'   => $center_office_id,
    'status'            => 'invoice',
    'booking_id'        => $funding['booking_id']['id'],
    'partner_id'        => $params['partner_id'],
    'funding_id'        => $params['id']
])->first();


// #todo - handle journal entries
// default credit account
// default downpayment account (debit)


// retrieve downpayment product
$downpayment_product_id = 0;

$downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku');
if($downpayment_sku) {
    $product = Product::search(['sku', '=', $downpayment_sku])->read(['id'])->first();
    if($product) {
        $downpayment_product_id = $product['id'];
    }
}

// create invoice line related to the downpayment
InvoiceLine::create([
    'invoice_id' => $invoice['id'],
    'product_id' => $downpayment_product_id,
    'unit_price' => $funding['due_amount'],
    'qty'        => 1,
    'vat_rate'   => 0.0
]);


// convert funding to 'invoice' type
$funding = Funding::id($params['id'])->update(['type' => 'invoice', 'invoice_id' => $invoice['id']]);


$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();