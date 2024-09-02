<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Funding;
use sale\order\Invoice;
use sale\order\Order;

list($params, $providers) = announce([
    'description'   => "Generate the funding for the (non-proforma) given invoice. And, in case of balance invoice, attaches non-invoiced (partially) paid fundings to it.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the invoice for which to create the funding(s).',
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
    'providers'     => ['context', 'orm', 'cron', 'auth']
]);
/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

$invoice = Invoice::id($params['id'])
    ->update(['balance' => null])
    ->read(['id', 'status', 'invoice_type',
            'is_downpayment', 'order_id',
            'funding_id', 'reversed_invoice_id',
            'price', 'balance', 'due_date'])
    ->first(true);

if($invoice['status'] != 'invoice') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

if(is_null($invoice['funding_id'])) {
    if($invoice['invoice_type'] == 'invoice') {
        $order = Order::id($invoice['order_id'])->read(['fundings_ids' => ['is_paid', 'invoice_id', 'due_amount',
                                                                            'paid_amount', 'amount_share']
            ])
            ->first(true);
        $invoice_price = round($invoice['balance'], 2);

        if($invoice_price != 0.00) {
            $new_funding = Funding::create([
                    'description'           => 'Final invoice',
                    'order_id'              => $invoice['order_id'],
                    'invoice_id'            => $invoice['id'],
                    'due_amount'            => $invoice_price,
                    'is_paid'               => false,
                    'type'                  => 'invoice',
                    'issue_date'            => time(),
                    'due_date'              => $invoice['due_date']
                ])
                ->read(['id', 'name'])
                ->first(true);

            Invoice::id($params['id'])->update(['funding_id' => $new_funding['id']]);
        }
    }
    elseif($invoice['invoice_type'] == 'credit_note') {
        $order = Order::id($invoice['order_id'])
            ->read(['fundings_ids' => [ 'is_paid', 'due_amount', 'paid_amount',
                                    'amount_share', 'invoice_id' => ['is_downpayment']]
            ])
            ->first(true);


        $paid_amount = array_reduce($order['fundings_ids'], function($c, $funding) {
                $result = $c;
                if(!isset($funding['invoice_id']['is_downpayment']) || !$funding['invoice_id']['is_downpayment']) {
                    $result += $funding['paid_amount'];
                }
                return $result;
            }, 0);

        if($paid_amount > 0) {
            $new_funding = Funding::create(
                    [
                        'description'           => 'Credit note',
                        'order_id'              => $invoice['order_id'],
                        'invoice_id'            => $invoice['id'],
                        'due_amount'            => round(-$paid_amount, 2),
                        'is_paid'               => false,
                        'funding_type'          => 'invoice',
                        'issue_date'            => time(),
                        'due_date'              => $invoice['due_date']
                    ]
                )
                ->read(['id', 'name'])
                ->first(true);

            Invoice::id($params['id'])->update(['funding_id' => $new_funding['id']]);
        }
        else {
            Invoice::id($params['id'])->update(['is_paid' => true]);
        }
    }
}

$context->httpResponse()
        ->status(204)
        ->send();