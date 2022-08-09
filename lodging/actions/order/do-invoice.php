<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\pos\Order;
use sale\pos\OrderPaymentPart;
use lodging\finance\accounting\Invoice;
use lodging\finance\accounting\InvoiceLine;
use lodging\sale\pay\Funding;

list($params, $providers) = announce([
    'description'   => "This will generate an invoice for the given order. Order is expected to be paid already.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order that must be invoiced.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user', 'pos.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// read order object
$order = Order::id($params['id'])
                ->read([
                    'id', 'name', 'status',
                    'has_invoice', 'invoice_id',
                    'session_id' => ['center_id' => ['organisation_id', 'center_office_id', 'pos_default_customer_id']],
                    'customer_id',
                    'order_payments_ids' => [
                        'order_lines_ids' => [
                            'product_id',
                            'price_id',
                            'qty',
                            'unit_price',
                            'vat_rate',
                            'discount',
                            'free_qty'
                        ],
                        'order_payment_parts_ids' => [
                            'payment_method', 'booking_id', 'voucher_ref'
                        ]
                    ]
                ])
                ->first();

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

// only paid orders can generate an invoice
if($order['status'] != 'paid') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// prevent multiple invoicing
if($order['invoice_id'] > 0) {
    throw new Exception("already_invoiced", QN_ERROR_INVALID_PARAM);
}

// prevent creating an invoice for default customer (POS anonymous customer)
if($order['customer_id'] == $order['session_id']['center_id']['pos_default_customer_id']) {
    throw new Exception("restricted_customer", QN_ERROR_INVALID_PARAM);
}

// create invoice and invoice lines
$invoice = Invoice::create([
        'date'              => time(),
        'organisation_id'   => $order['session_id']['center_id']['organisation_id'],
        'center_office_id'  => $order['session_id']['center_id']['center_office_id'],
        'status'            => 'proforma',
        'partner_id'        => $order['customer_id']
    ])
    ->first();

// attach products on invoice, based on order lines
foreach($order['order_payments_ids'] as $payment_id => $payment) {
    // create as many lines as payment parts
    foreach($payment['order_lines_ids'] as $lid => $line) {
        $i_line = [
            'invoice_id'                => $invoice['id'],
            'product_id'                => $line['product_id'],
            'price_id'                  => $line['price_id'],            
            'vat_rate'                  => $line['vat_rate'],
            'unit_price'                => $line['unit_price'],
            'qty'                       => $line['qty'],
            'free_qty'                  => $line['free_qty'],
            'discount'                  => $line['discount']
        ];
        InvoiceLine::create($i_line);
    }
}

// emit the invoice (changing status will trigger an invoice number assignation)
$invoice = Invoice::id($invoice['id'])->update(['status' => 'invoice'])->read(['price', 'due_date'])->first();

// create a new funding relating to the invoice
$funding = Funding::create([
        'description'           => 'Facture de ticket',
        'invoice_id'            => $invoice['id'],
        'booking_id'            => null,
        'center_office_id'      => $order['session_id']['center_id']['center_office_id'],
        'due_amount'            => $invoice['price'],
        'type'                  => 'invoice',
        'amount_share'          => 1.0,
        'order'                 => 10,
        'issue_date'            => time(),
        'due_date'              => $invoice['due_date']
    ])
    ->first();

// attach order payments (order_payment_part) to the funding
foreach($order['order_payments_ids'] as $pid => $payment) {
    OrderPaymentPart::ids($payment['order_payment_parts_ids'])->update(['funding_id' => $funding['id']]);
}

// atatch the invoice to the Order, and mark it as having an invoice
Order::id($params['id'])->update(['has_invoice' => true, 'invoice_id' => $invoice['id']]);

$context->httpResponse()
        ->status(204)
        ->send();