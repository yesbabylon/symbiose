<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Renew a subscription by creating missing subscription entries and shifting its period.',
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
    'providers'   => ['context', 'cron']
]);

/**
 * @var \equal\php\Context      $context
 * @var \equal\cron\Scheduler   $cron
 */
list($context, $cron) = [$providers['context'], $providers['cron']];

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

$today = strtotime(date('Y-m-d'));
$renewals_count = 0;
$max_renewals_count = 120;

while($renewals_count < $max_renewals_count) {
    eQual::run('do', 'sale_subscription_add-subscriptionentry', ['id' => $subscription['id']]);

    if($subscription['date_to'] >= $today) {
        break;
    }

    eQual::run('do', 'sale_subscription_shift-period', ['id' => $subscription['id']]);

    $subscription = Subscription::id($subscription['id'])
        ->read(['id', 'date_to', 'is_auto_renew'])
        ->first(true);

    ++$renewals_count;
}

if($renewals_count >= $max_renewals_count) {
    throw new Exception('too_many_renewals', QN_ERROR_INVALID_PARAM);
}

Subscription::id($subscription['id'])
    ->update([
        'is_expired'          => null,
        'has_upcoming_expiry' => null
    ]);

$cron->cancel("subscription.{$subscription['id']}.renew");
$cron->cancel("subscription.{$subscription['id']}.create.subscriptionEntry");
$cron->schedule(
    "subscription.{$subscription['id']}.renew",
    $subscription['date_to'],
    'sale_subscription_renew',
    ['id' => $subscription['id']]
);

$context->httpResponse()
    ->status(204)
    ->send();
