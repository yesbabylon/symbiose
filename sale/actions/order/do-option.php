<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;
use core\setting\Setting;

list($params, $providers) = eQual::announce([
    'description'   => "Update the status of given order to 'option'.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted order.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
        'no_expiry' =>  [
            'description'   => 'The option will remain active without time limit.',
            'type'          => 'boolean',
            'default'       => false
        ],
        'days_expiry' =>  [
            'description'   => 'The number of days for the option to expire.',
            'type'          => 'integer',
            'default'       =>  Setting::get_value('sale', 'order', 'option.validity', 10)
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
        'date_expiry',
        'is_noexpiry'
    ])
    ->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

if($order['status'] != 'quote') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

$days_expiry = $params['days_expiry'];

if($params['no_expiry']) {
    Order::id($order['id'])->update(['is_noexpiry' => true]);
}
else {
    Order::id($params['id'])
        ->update([
            'is_noexpiry' => false,
            'date_expiry' => strtotime(' +' . $days_expiry . ' days')
        ]);
}

Order::id($params['id'])->update(['status' => 'option']);

$context->httpResponse()
        ->status(204)
        ->send();