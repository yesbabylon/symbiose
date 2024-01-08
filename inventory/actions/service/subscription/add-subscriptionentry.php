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
    'providers'   => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$subscription = Subscription::id($params['id'])
    ->read([
        'id',
        'date_from',
        'date_to',
        'price_id',
        'price'
    ])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}

$subscription_entry = SubscriptionEntry::search([
    ['subscription_id', '=', $subscription['id']],
    ['date_from', '=', $subscription['date_from']],
    ['date_to', '=', $subscription['date_to']]
])
    ->read(['id'])
    ->first();

if (!$subscription_entry) {
    $subscription_entry = SubscriptionEntry::create([
        'subscription_id' => $subscription['id'],
        'date_from'       => $subscription['date_from'],
        'date_to'         => $subscription['date_to'],
        'price_id'        => $subscription['price_id'],
        'price'           => $subscription['price']
    ])
        ->first();
}

$context->httpResponse()
        ->status(204)
        ->send();
