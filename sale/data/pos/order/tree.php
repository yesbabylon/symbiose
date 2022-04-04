<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\pos\Order;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking quote as attachment.",
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the booking related to the sending of the email.',
            'type'          => 'integer',
            'required'      => true
        ],
        'variant' =>  [
            'description'   => 'Type of tree being requested.',
            'type'          => 'string',
            'selection'     => [
                'lines',        // tree with order lines
                'payments'      // tree with order payments
            ],
            'default'       => 'lines'
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['pos.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

list($context) = [$providers['context']];


$tree = [];

switch($params['variant']) {
    case 'lines':
        $tree = [
            'id',
            'name',
            'created',
            'status',
            'has_invoice',
            'has_funding',
            'total',
            'price',
            'order_lines_ids' => [
                'id',
                'order_id',
                'name',
                'unit_price',
                'vat_rate',
                'qty',
                'discount',
                'free_qty',
                'total',
                'price'
            ]
        ];
        break;
    case 'payments':
        $tree = [
            'id',
            'name',
            'created',
            'status',
            'has_invoice',
            'has_funding',
            'total',
            'price',
            'order_payments_ids' => [
                'id',
                'order_id',
                'total_due',
                'total_paid',
                'order_lines_ids' => [
                    'id',
                    'order_id',
                    'order_payment_id',
                    'name',
                    'unit_price',
                    'vat_rate',
                    'qty',
                    'discount',
                    'free_qty',
                    'total',
                    'price'
                ],
                'order_payment_parts_ids' => [
                    'id',
                    'order_payment_id',
                    'amount',
                    'payment_method',
                    'booking_id',
                    'voucher_ref'
                ]
            ]
        ];
        break;
}

$orders = Order::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$orders || !count($orders)) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$order = reset($orders);


$context->httpResponse()
        ->body($order)
        ->send();