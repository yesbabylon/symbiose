<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Invoice;
use sale\accounting\invoice\InvoiceLine;
use sale\accounting\invoice\InvoiceLineGroup;
use sale\order\Order;
use sale\order\Funding;

list($params, $providers) = eQual::announce([
    'description'   => "Generate a proforma balance invoice for a given order.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order for which the invoice has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['order.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm',]
]);


list($context, $orm) = [$providers['context'], $providers['orm']];


$invoice = Invoice::search(['order_id', '=', $params['id']],
                           ['invoice_type', '=', 'invoice'],
                           ['status', '=', 'invoice'])
            ->read(['id'])
            ->first(true);

if($invoice) {
    throw new Exception("invoice_already_exists", QN_ERROR_NOT_ALLOWED);
}

$order = Order::id($params['id'])
    ->read([
        'id',
        'status',
        'price',
        'delivery_date',
        'contracts_ids',
        'customer_id' => [
            'id'
        ],
        'fundings_ids' => [
            'is_paid',
            'due_amount',
            'paid_amount'
        ],
        'order_lines_ids',
        'order_lines_groups_ids' => [
            'name',
            'fare_benefit',
            'total',
            'price',
            'order_lines_ids' => [
                'product_id',
                'description',
                'price_id',
                'unit_price',
                'vat_rate',
                'qty',
                'free_qty',
                'discount',
                'price',
                'total'
            ]
        ]
    ])
    ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($order['status'], ['confirmed', 'checkedout'])) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

if(count($order['order_lines_ids']) <= 0) {
    throw new Exception("empty_order", QN_ERROR_INVALID_PARAM);
}

$proforma = Invoice::search([['order_id', '=', $order['id']],
                             ['invoice_type', '=', 'invoice'],
                             ['status', '=', 'proforma']])
            ->read(['id', 'fundings_ids'])
            ->first(true);

if($proforma) {
    Invoice::id($proforma['id'])->delete(true);
    Funding::ids($proforma['fundings_ids'])->update(['invoice_id' => null]);
}

$order_lines_ids = [];

$invoice = Invoice::create([
        'order_id'          => $order['id'],
        'customer_id'       => $order['customer_id']['id']
    ])
    ->update(['customer_id'       => $order['customer_id']['id']])
    ->read(['id','name', 'invoice_number','status', 'customer_id'])
    ->first(true);

foreach($order['order_lines_groups_ids'] as $group_id => $group) {

    $invoice_line_group = InvoiceLineGroup::create([
            'name'              => $group['name'],
            'invoice_id'        => $invoice['id']
        ])
        ->read(['id'])
        ->first(true);

    foreach($group['order_lines_ids'] as $lid => $line) {
        $order_lines_ids[] = $lid;

        InvoiceLine::create([
                'invoice_id'                => $invoice['id'],
                'invoice_line_group_id'     => $invoice_line_group['id'],
                'product_id'                => $line['product_id'],
                'description'               => $line['description'],
                'price_id'                  => $line['price_id'],
                'unit_price'                => $line['unit_price'],
                'vat_rate'                  => $line['vat_rate'],
                'qty'                       => $line['qty'],
                'free_qty'                  => $line['free_qty'],
                'discount'                  => $line['discount'],
            ])
            ->do('reset_invoice_prices')
            ->first();

    }

}

$context->httpResponse()
        ->status(204)
        ->send();
