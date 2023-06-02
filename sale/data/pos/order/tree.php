<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\pos\Order;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Provide a fully loaded tree for a given order.",
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the order for which the tree is requested.',
            'type'          => 'integer',
            'required'      => true
        ],
        'variant' =>  [
            'description'   => 'Type of tree being requested.',
            'type'          => 'string',
            'selection'     => [
                'lines',        // tree with order lines
                'payments',     // tree with order payments
                'ticket'
            ],
            'default'       => 'lines'
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
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
            'total',
            'price',
            'total_paid',
            'session_id' => [
                'id', 'name', 'status'
            ],
            'customer_id' => [
                'name',
                'partner_identity_id' => [
                    'id',
                    'has_vat',
                    'vat_number'
                ]
            ],
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
                'price',
                'has_funding',
                'funding_id',
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
            'total_paid',
            'session_id' => [
                'id', 'name', 'status'
            ],
            'customer_id' => [
                'name',
                'partner_identity_id' => [
                    'id',
                    'has_vat',
                    'vat_number',
                    'address_city',
                    'address_zip',
                    'address_street'
                ]
            ],
            'order_payments_ids' => [
                'id',
                'order_id',
                'total_due',
                'total_paid',
                'status',
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
                    'price',
                    'has_funding',
                    'funding_id'
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
    case 'ticket':
        $tree = [
            'id',
            'name',
            'created',
            'status',
            'total',
            'price',
            'total_paid',
            'has_invoice',
            'invoice_id',
            'session_id' => [
                'amount',
                'user_id',
                'center_id' => [
                    'name',
                    'phone',
                    'email',
                    'organisation_id' => [
                        'legal_name',
                        'phone',
                        'email',
                        'vat_number'
                    ],
                    'center_office_id' => [
                        'name',
                        'address_street',
                        'address_city',
                        'address_zip'
                    ]
                ]
            ],
            'customer_id' => [
                'name',
                'partner_identity_id'=> [
                    'id',
                    'has_vat',
                    'vat_number',
                    'address_city',
                    'address_zip',
                    'address_street'
                ]
            ],
            'order_lines_ids' => [
                'id',
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
}

$orders = Order::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$orders || !count($orders)) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$order = reset($orders);

$context->httpResponse()
        ->body($order)
        ->send();