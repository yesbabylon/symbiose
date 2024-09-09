<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Order;
use sale\order\Funding;
use sale\order\Invoice;
list($params, $providers) = eQual::announce([
    'description'   => "Generates the proforma for the balance invoice for a order.",
    'params'        => [
        'id' =>  [
            'description'       => 'Identifier of the order for which the invoice has to be generated.',
            'type'              => 'integer',
            'required'          => true
        ]
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
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];


$order = Order::id($params['id'])
                  ->read(['id', 'status', 'order_lines_ids', 'customer_id','customer_identity_id'])
                  ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if($order['status'] != 'checkedout') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}


$deposit_invoices = Invoice::search([
                        ['order_id', '=', $order['id']],
                        ['is_downpayment', '=', true]
                ])
                ->read(['status'])
                ->get(true);

foreach($deposit_invoices as $deposit_invoice) {
    if($deposit_invoice['status'] == 'proforma') {
        throw new Exception("non_emitted_deposit_invoice", QN_ERROR_INVALID_PARAM);
    }
}

Funding::search([ ['paid_amount', '=', 0], ['order_id', '=', $params['id']]])->delete(true);

eQual::run('do', 'sale_order_invoice_generate', ['id' => $order['id']]);

Order::id($order['id'])->update(['status' => 'invoiced']);

$context->httpResponse()
        ->status(204)
        ->send();