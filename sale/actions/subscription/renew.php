<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Renew a subscription by creating the sale entry for its current period.',
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
    ->read(['id', 'date_to', 'is_auto_renew'])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}

if(!$subscription['is_auto_renew']) {
    $context->httpResponse()
        ->status(204)
        ->send();
    return;
}

if(!isset($subscription['date_to'])) {
    throw new Exception('missing_date_to', QN_ERROR_INVALID_PARAM);
}

eQual::run('do', 'sale_subscription_add-subscriptionentry', ['id' => $subscription['id']]);

Subscription::id($subscription['id'])
    ->update([
        'is_expired'          => null,
        'has_upcoming_expiry' => null
    ]);

$context->httpResponse()
    ->status(204)
    ->send();
