<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Invoice;
use sale\order\Order;
use sale\order\Funding;


list($params, $providers) = announce([
    'description'   => "Reverse an invoice by creating a credit note (only invoices -not credit notes- can be reversed).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice to reverse.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'groups'            => ['order.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];


$invoice = Invoice::id($params['id'])
    ->read([
        'status',
        'invoice_type',
        'order_id',
        'funding_id',
        'organisation_id',
        'customer_id',
        'is_paid',
        'is_downpayment'
    ])
    ->first(true);

if(!$invoice) {
    throw new Exception("unknown_invoice", QN_ERROR_UNKNOWN_OBJECT);
}

if($invoice['invoice_type'] != 'invoice') {
    throw new Exception("incompatible_type", QN_ERROR_UNKNOWN_OBJECT);
}


if($invoice['status'] != 'invoice') {
    throw new Exception("incompatible_status", QN_ERROR_UNKNOWN_OBJECT);
}

Invoice::id($params['id'])->transition('cancel');

Invoice::search(['reversed_invoice_id', '=', $params['id']])->update(['order_id'   => $invoice['order_id']]);

if(!is_null($invoice['funding_id'])) {
    $funding = Funding::id($invoice['funding_id'])->read(['paid_amount', 'is_paid'])->first(true);
    if($funding['paid_amount'] == 0 && !$funding['is_paid']) {
        Funding::id($invoice['funding_id'])->delete(true);
    }
    else {
        Funding::id($invoice['funding_id'])->update(['type' => 'installment']);
    }
}

if(!$invoice['is_downpayment'] && isset($invoice['order_id'])) {
    Order::id($invoice['order_id'])
        ->update(['status' => 'checkedout'])
        ->update(['is_invoiced' => false]);
}

$context->httpResponse()
        ->status(204)
        ->send();
