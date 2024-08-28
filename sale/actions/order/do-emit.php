<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use sale\order\Invoice;
use sale\order\Order;
use sale\order\Funding;

list($params, $providers) = eQual::announce([
    'description'   => "Emit a new invoice from an existing proforma and update related order, if necessary.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice to emit.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['order.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);
/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $om
 */
list($context, $om) = [$providers['context'], $providers['orm']];

$invoice = Invoice::id($params['id'])
    ->read(['id', 'state', 'deleted', 'emission_date', 'status', 'invoice_type', 'organisation_id', 'price', 'order_id', 'invoice_lines_ids'])
    ->first(true);

if(!$invoice) {
    throw new Exception("unknown_invoice", QN_ERROR_UNKNOWN_OBJECT);
}

if($invoice['deleted'] || $invoice['state'] != 'instance' || $invoice['status'] != 'proforma') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

if(count($invoice['invoice_lines_ids']) <= 0) {
    throw new Exception("empty_invoice", QN_ERROR_INVALID_PARAM);
}

$year = date('Y', $invoice['emission_date']);

$fiscal_year = Setting::get_value('sale', 'invoice', 'fiscal_year');

if(!$fiscal_year) {
    throw new Exception('missing_fiscal_year', EQ_ERROR_INVALID_CONFIG);
}

if(intval($year) != intval($fiscal_year)) {
    throw new Exception('fiscal_year_mismatch', EQ_ERROR_CONFLICT_OBJECT);
}

if(!is_null($invoice['order_id'])) {
    $order = Order::id($invoice['order_id'])
        ->read([
                'id',
                'name',
                'status',
                'price',
                'reversed_invoice_id',
                'invoices_ids' => [
                    'id', 'emission_date', 'invoice_type', 'status', 'price'
                ]
            ])
        ->first(true);

    if(!$order) {
        throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
    }

    if($invoice['invoice_type'] == 'invoice' && $order['status'] != 'invoiced') {
        throw new Exception("incompatible_order_status", QN_ERROR_INVALID_PARAM);
    }

    foreach($order['invoices_ids'] as $id => $order_invoice) {
        if($order_invoice['id'] != $invoice['id'] &&
           $order_invoice['status'] == 'proforma' &&
           $order_invoice['invoice_type'] == $invoice['invoice_type'] &&
           $order_invoice['date'] <= $invoice['date']) {

            throw new Exception("existing_previous_invoice", QN_ERROR_INVALID_PARAM);
        }
    }

    $sum_invoices = ($invoice['status'] == 'invoice' && $invoice['invoice_type'] == 'invoice')?$invoice['price']:0.0;
    foreach($order['invoices_ids'] as $oid => $odata) {
        if($odata['status'] == 'invoice' && $type == 'invoice') {
            $sum_invoices += ($odata['invoice_type'] == 'invoice')? $odata['price'] : -($odata['price']);
        }
    }

    if(round($sum_invoices, 2) > round($order['price'], 2)) {
        throw new Exception("exceeding_order_price", QN_ERROR_INVALID_PARAM);
    }

    Invoice::generateNumberInvoice((array) $invoice['id']);

    $invoice_test =Invoice::id($invoice['id'])
        ->read(['name','invoice_number'])->first(true);
}
else {
    throw new Exception('invalid_invoice', EQ_ERROR_UNKNOWN);
}

$context->httpResponse()
        ->status(205)
        ->send();