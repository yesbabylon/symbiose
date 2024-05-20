<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Bulk update of expiration and subscription expiration check".',
    'params'      => [
        'ids' => [
            'description'       => 'List of Subscription identifiers the check against emptyness.',
            'type'              => 'array'
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


$expired_conditions = [
    ['date_to', '<', date('Y-m-d', time())],
    ['is_expired', '=', false]
];

$upcoming_expiry_conditions = [
    ['date_to', '<', date('Y-m-d', strtotime('+30 days'))],
    ['has_upcoming_expiry', '=', false]
];

if (!empty($params['ids'])) {
    $expired_conditions[] = ['id', 'in', $params['ids']];
    $upcoming_expiry_conditions[] = ['id', 'in', $params['ids']];
}

$should_be_updated_ids = array_merge(
    Subscription::search($expired_conditions)->ids(),
    Subscription::search($upcoming_expiry_conditions)->ids()
);

eQual::run('do', 'sale_subscription_update-expirations', ['ids' => $should_be_updated_ids] );

$should_have_alert_ids = Subscription::ids($should_be_updated_ids)->read(['id','has_upcoming_expiry','is_expired'])->get(true);

foreach($should_have_alert_ids as $subscription_id) {
    eQual::run('do', 'sale_subscription_check-expiration', ['id' => $subscription_id]);
}

$context->httpResponse()
        ->status(204)
        ->send();
