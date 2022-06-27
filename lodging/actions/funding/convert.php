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
            'description'   => 'Identifier of the partner (organisation) to who the invoice has to be emitted (can be arbitrary).',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'payment_terms_id' =>  [
            'description'   => 'Identifier of the payment terms to apply.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
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


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$partners = Partner::search(['id', '=', $params['partner_id']])->get();

if(!count($partners)) {
    throw new Exception("unknown_partner", QN_ERROR_UNKNOWN_OBJECT);
}

$funding = Funding::id($params['id'])
                    ->read([
                        'booking_id' => [
                            'date_from',
                            'center_id' => [ 'organisation_id', 'center_office_id', 'price_list_category_id' ]
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
    'center_office_id'  => $center_office_id,
    'booking_id'        => $funding['booking_id']['id'],
    'partner_id'        => $params['partner_id'],
    'funding_id'        => $params['id'],
    'payment_terms_id'  => $params['payment_terms_id']
])->first();


// #todo - create scheduled tasks for setting payment_status

// retrieve downpayment product
$downpayment_product_id = 0;

$downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$organisation_id);
if($downpayment_sku) {
    $product = Product::search(['sku', '=', $downpayment_sku])->read(['id'])->first();
    if($product) {
        $downpayment_product_id = $product['id'];
    }
}

/* 
    Find vat rule, based on Price for product for current year
*/
$vat_rate = 0.0;

// find suitable price list
$pricelist_category_id = $funding['booking_id']['center_id']['price_list_category_id'];
$price_lists_ids = $orm->search('sale\price\PriceList', [
        ['price_list_category_id', '=', $pricelist_category_id],
        ['date_from', '<=', $funding['booking_id']['date_from']],
        ['date_to', '>=', $funding['booking_id']['date_from']],
        ['status', 'in', ['published']]
    ],
    ['is_active' => 'desc']
);

// search for a matching Price within the found Price List
foreach($price_lists_ids as $price_list_id) {
    // there should be one or zero matching pricelist with status 'published', if none of the found pricelist
    $prices_ids = $orm->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $downpayment_product_id]]);
    if($prices_ids > 0 && count($prices_ids)) {
        $price_id = reset($prices_ids);
        $price = Price::id($price_id)->read(['vat_rate'])->first();
        $vat_rate = $price['vat_rate'];
    }
}

// #memo - funding already includes the VAT, if any (funding due_amount cannot be changed)
$unit_price = $funding['due_amount'];

if($vat_rate > 0) {
    // deduct VAT from due amount
    $unit_price = round($unit_price / (1+$vat_rate), 4);
}

// create invoice line related to the downpayment
InvoiceLine::create([
    'invoice_id' => $invoice['id'],
    'product_id' => $downpayment_product_id,
    'unit_price' => $unit_price,
    'qty'        => 1,
    'vat_rate'   => $vat_rate
]);

// convert funding to 'invoice' type
$funding = Funding::id($params['id'])->update(['type' => 'invoice', 'invoice_id' => $invoice['id']]);

$context->httpResponse()
        ->status(204)        
        ->send();