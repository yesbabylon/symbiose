<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Update subscription expiration.',
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
    ->read(['id','date_to', 'is_expired', 'has_upcoming_expiry'])
    ->first(true);

if(!array_filter($subscription)) {
    throw new Exception("unknown_subscription", QN_ERROR_UNKNOWN_OBJECT);
}

if((!$subscription['is_expired']) && ($subscription['date_to'] < time())){
    Subscription::id($subscription['id'])->update(['is_expired' => null]);
}

if(!$subscription['has_upcoming_expiry'] && $subscription['date_to'] <  strtotime('+30 days')){
    Subscription::id($subscription['id'])->update(['has_upcoming_expiry' => null]);
}

$context->httpResponse()
        ->status(204)
        ->send();
