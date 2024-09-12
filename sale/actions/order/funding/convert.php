<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Funding;
use sale\order\Invoice;
use sale\accounting\invoice\InvoiceLine;
use sale\accounting\invoice\InvoiceLineGroup;
use sale\catalog\Product;
use sale\price\Price;
use sale\price\PriceList;
use core\setting\Setting;


list($params, $providers) = eQual::announce([
    'description'   => "Convert given funding to a downpayment invoice.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the funding to be converted.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['order.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context                  $context
 */
list($context) = [$providers['context']];

$funding = Funding::id($params['id'])
                    ->read(['id','funding_type', 'due_amount',
                            'order_id' => ['id', 'customer_id', 'delivery_date']])
                    ->first(true);

if(!$funding) {
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if($funding['type'] == 'invoice') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

$downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment_sku');
if (!$downpayment_sku) {
    throw new Exception("missing_setting_downpayment", QN_ERROR_UNKNOWN_OBJECT);
}

$downpayment_product = Product::search(['sku', '=', $downpayment_sku])->read(['id','name'])->first(true);
if (empty($downpayment_product)) {
    throw new Exception("unknown_product", QN_ERROR_UNKNOWN_OBJECT);
}

$order = $funding['order_id'];

$invoice = Invoice::create([
        'order_id'          => $order['id'],
        'customer_id'       => $order['customer_id'],
        'funding_id'        => $funding['id'],
        'is_downpayment'    => true
    ])
    ->update(['customer_id'       => $order['customer_id']])
    ->read(['id','name', 'invoice_number','status', 'customer_id'])
    ->first(true);

$invoice_line_group = InvoiceLineGroup::create([
        'invoice_id' => $invoice['id'],
        'name'       => $downpayment_product['name']
    ])
    ->read(['id'])
    ->first(true);

$price_lists_ids = PriceList::search(
    [
        ['date_from', '<=', strtotime(date('Y-01-01 00:00:00', $order['delivery_date']))],
        ['date_to', '>=', strtotime(date('Y-12-31 23:59:59', $order['delivery_date']))],
        ['status', '=', 'published'],
    ])
    ->ids();

$vat_rate = 0.0;
foreach($price_lists_ids as $price_list_id) {
    $prices_ids = Price::search([ ['price_list_id', '=', $price_list_id], ['product_id', '=', $downpayment_product_id]])->get();
    if($prices_ids) {
        $prices = Price::ids($prices_ids)->read(['vat_rate']);
        $price = reset($prices);
        $vat_rate = $price['vat_rate'];
    }
}

$unit_price = $funding['due_amount'];
if($vat_rate > 0) {
    $unit_price = round($unit_price / (1+$vat_rate), 4);
}


InvoiceLine::create([
    'invoice_id'                => $invoice['id'],
    'invoice_line_group_id'     => $invoice_line_group['id'],
    'product_id'                => $downpayment_product['id'],
    'description'               => $downpayment_product['description'],
    'vat_rate'                  => $vat_rate,
    'unit_price'                => $unit_price,
    'qty'                       => 1
])
->do('reset_invoice_prices')
->first();


Funding::id($funding['id'])->update(['funding_type' => 'invoice', 'invoice_id' => $invoice['id']]);


$context->httpResponse()
        ->status(204)
        ->send();