<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Order;
use sale\order\Contract;
use sale\order\ContractLine;
use sale\order\ContractLineGroup;
use sale\order\Funding;


list($params, $providers) = announce([
    'description'   => "Sets order as confirmed, creates contract and generates payment plan.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order to mark as confirmed.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
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

if($order['status'] != 'option') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

Contract::ids($order['contracts_ids'])->update(['status' => 'cancelled']);
$order_lines_ids = [];
$contract = Contract::create([
        'date'          => time(),
        'status'        => 'pending',
        'order_id'      => $params['id'],
        'valid_until'   => strtotime('+30 days'),
        'customer_id'   => $order['customer_id']['id']
    ])
    ->read(['id'])
    ->first(true);

foreach($order['order_lines_groups_ids'] as $group) {
    $group_id = $group['id'];
    $group_label = $group['name'].' : ' . date('d/m/y', time());

    $contract_line_group = ContractLineGroup::create([
            'name'              => $group_label,
            'is_pack'           => false,
            'contract_id'       => $contract['id'],
            'fare_benefit'      => $group['fare_benefit']
        ])
        ->first(true);

        foreach($group['order_lines_ids'] as $line) {
        $contract_line = [
            'contract_id'               => $contract['id'],
            'contract_line_group_id'    => $contract_line_group['id'],
            'product_id'                => $line['product_id'],
            'description'               => $line['description'],
            'price_id'                  => $line['price_id'],
            'vat_rate'                  => $line['vat_rate'],
            'unit_price'                => $line['unit_price'],
            'qty'                       => $line['qty'],
            'free_qty'                  => $line['free_qty'],
            'discount'                  => $line['discount']
        ];

        $contractLine = ContractLine::create($contract_line)
            ->update([
                'total'       => $line['total'],
                'price'       => $line['price']
            ]);

    }
}

if ($order['fundings_ids']){
    eQual::run('do', 'sale_order_funding_update', ['ids' =>  array_values($order['fundings_ids'])]);
}

$fundings_handled_sum = 0.0;
$fundings_paid = Funding::search([['order_id' , '=', $order['id']] , ['is_paid' , "=", true]])
    ->read(['paid_amount'])
    ->get();

$fundings_handled_sum = array_sum(array_column($fundings_paid, 'paid_amount'));

$remaining_amount = $order['price'] - $fundings_handled_sum;
if($order['price'] > 0 && ($remaining_amount/$order['price']) > 0.1) {
    $funding = [
        'order_id'              => $order['id'],
        'due_amount'            => $remaining_amount,
        'funding_type'          => 'installment',
        'is_paid'               => false,
        'due_date'              => $order['delivery_date'],
        'description'           => 'Full'
    ];
    Funding::create($funding)->read(['name']);
}

Order::id($order['id'])->update(['status' => 'confirmed']);

$context->httpResponse()
        ->status(204)
        ->send();