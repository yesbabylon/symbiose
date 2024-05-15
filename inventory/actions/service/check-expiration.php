<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Update expiration and verify subscription expiration.',
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
    'providers'     => ['context', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $dispatch) = [ $providers['context'], $providers['dispatch']];

$subscription_id = Subscription::id($params['id'])->ids();
if(!$subscription_id) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}
$subscription_id = reset($subscription_id);

eQual::run('do', 'sale_subscription_update-expiration', ['id' => $subscription_id]);

$subscription = Subscription::id($subscription_id)
    ->read([
        'id',
        'is_expired',
        'has_upcoming_expiry',
    ])
    ->first(true);

$result = [];
$httpResponse = $context->httpResponse()->status(200);

if($subscription['is_expired'] || $subscription['has_upcoming_expiry']) {
    $result = $subscription['id'];
    $dispatch->dispatch('inventory.subscription.check.expiration', 'inventory\service\Subscription', $subscription['id'], 'important', 'inventory_service_check-expiration', ['id' => $params['id']], [], null, null);
    $httpResponse->status(qn_error_http(QN_ERROR_NOT_ALLOWED));
}
else {
    $dispatch->cancel('inventory.subscription.check.expiration', 'inventory\service\Subscription', $subscription['id']);
}

$httpResponse->body($result)
             ->send();

