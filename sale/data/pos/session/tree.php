<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\pos\CashdeskSession;
use sale\pos\Order;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Provide a fully loaded tree for a given session.",
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
                'session'        
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
    case 'session':
        $tree = [
            'id',
            'amount',
            'user_id',
            'cashdesk_id',
            'status',
            'orders_ids' => [
                'id',
                'name',
                'created',
                'status',
                'has_invoice',
                'has_funding',
                'total',
                'price',
                'customer_id'=>[
                    'name',
                    'partner_identity_id'=>[
                        'vat_number'
                    ]
                ],
                'total_paid',
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
                ],
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
            ]    
        ];
        break;
}

$cashdesksessions = CashdeskSession::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$cashdesksessions || !count($cashdesksessions)) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$cashdesksession = reset($cashdesksessions);


$context->httpResponse()
        ->body($cashdesksession)
        ->send();