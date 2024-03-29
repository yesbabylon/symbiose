<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use inventory\service\Subscription;
use inventory\service\SubscriptionEntry;

list($params, $providers) = eQual::announce([
    'description' => 'Create an entry from a subscription.',
    'params'      => [
        'id' =>  [
            'description' => 'ID of the subscription.',
            'type'        => 'integer',
            'required'    => true
        ]
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

$subscription = Subscription::id($params['id'])
    ->read([
        'id',
        'is_internal',
        'is_billable',
        'customer_id',
        'product_id',
        'date_from',
        'date_to',
        'price_id',
        'price'
    ])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}

if($subscription['is_internal'] || empty($subscription['customer_id'])) {
    throw new Exception('internal_subscription_cannot_generate_sale_entry', QN_ERROR_NOT_ALLOWED);
}

$subscription_entry = SubscriptionEntry::search([
        ['subscription_id', '=', $subscription['id']],
        ['date_from', '=', $subscription['date_from']],
        ['date_to', '=', $subscription['date_to']]
    ])
    ->read(['id'])
    ->first();

if(!$subscription_entry) {
    $subscription_entry = SubscriptionEntry::create([
            'object_id'       => $subscription['id'],
            'is_billable'     => $subscription['is_billable'],
            'customer_id'     => $subscription['customer_id'],
            'product_id'      => $subscription['product_id'],
            'date_from'       => $subscription['date_from'],
            'date_to'         => $subscription['date_to'],
            'price_id'        => $subscription['price_id']
        ])
        ->update([
            'subscription_id' => $subscription['id'],
            'unit_price'      => $subscription['price']
        ])
        ->first();
}

$context->httpResponse()
        ->status(204)
        ->send();
