<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;
use sale\subscription\SubscriptionEntry;

list($params, $providers) = eQual::announce([
    'description' => 'Shift subscription dates depending on duration.',
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
        'date_from',
        'date_to',
        'duration'
    ])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', EQ_ERROR_UNKNOWN_OBJECT);
}

$subscription_entry = SubscriptionEntry::search([
        ['subscription_id', '=', $subscription['id']],
        ['date_from', '=', $subscription['date_from']],
        ['date_to', '=', $subscription['date_to']]
    ])
    ->read(['id'])
    ->first();

if(!$subscription_entry) {
    throw new Exception('missing_sale_entry_for_current_period', EQ_ERROR_NOT_ALLOWED);
}

Subscription::id($subscription['id'])
    ->update([
        // #memo - this triggers an update of `date_to` based on `duration`
        'date_from' => strtotime('+1day', $subscription['date_to'])
    ]);

$context->httpResponse()
        ->status(204)
        ->send();
