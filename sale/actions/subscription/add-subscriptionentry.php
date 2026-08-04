<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;
use sale\price\Price;

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

/** @var \equal\php\Context $context */
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
        'pricing_mode',
        'price_id',
        'price'
    ])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}

if($subscription['is_internal'] || empty($subscription['customer_id'])) {
    throw new Exception('internal_subscription_cannot_generate_sale_entry', EQ_ERROR_NOT_ALLOWED);
}

if(!isset($subscription['product_id'])) {
    throw new Exception('product_id_missing_from_subscription', EQ_ERROR_INVALID_PARAM);
}

$pricing_mode = $subscription['pricing_mode'] ?? 'fixed';
if(!isset($subscription['price_id']) || ($pricing_mode === 'fixed' && !isset($subscription['price']))) {
    throw new Exception('pricing_mode_missing_from_subscription', EQ_ERROR_INVALID_PARAM);
}

$subscription_entry = SubscriptionEntry::search([
        ['subscription_id', '=', $subscription['id']],
        ['date_from', '=', $subscription['date_from']],
        ['date_to', '=', $subscription['date_to']]
    ])
    ->read(['id'])
    ->first();

if(!$subscription_entry) {
    $values = [
            'object_id'       => $subscription['id'],
            'is_billable'     => $subscription['is_billable'],
            'customer_id'     => $subscription['customer_id'],
            'product_id'      => $subscription['product_id'],
            'date_from'       => $subscription['date_from'],
            'date_to'         => $subscription['date_to'],
            'price_id'        => $subscription['price_id'],
            'qty'             => 1.0
        ];

    $unit_price = null;
    if($pricing_mode === 'fixed') {
        $unit_price = $subscription['price'];
    }
    else {
        $price = Price::id($subscription['price_id'])
            ->read(['price'])
            ->first();

        if(!isset($price['price'])) {
            throw new Exception('price_missing_from_subscription', QN_ERROR_INVALID_PARAM);
        }

        $unit_price = $price['price'];
    }

    $subscription_entry = SubscriptionEntry::create($values)
        ->update([
            'subscription_id' => $subscription['id']
        ]);

    $subscription_entry = $subscription_entry
        ->update([
            'unit_price' => $unit_price
        ]);

    if($pricing_mode === 'fixed') {
        $subscription_entry = $subscription_entry
            ->transition('validate');
    }

    $subscription_entry = $subscription_entry->first();
}

$context->httpResponse()
        ->status(204)
        ->send();
