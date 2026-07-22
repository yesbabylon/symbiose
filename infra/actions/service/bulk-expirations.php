<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use infra\service\Subscription;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Bulk update and verify subscription expiration.",
    'params' 		=>	[
        'ids' => [
            'description'       => 'List of subscription identifiers.',
            'type'              => 'array'
        ]
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

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

if(!empty($should_be_updated_ids)) {
    $should_be_expired_ids = Subscription::search([
            ['id', 'in', $should_be_updated_ids],
            ['date_to', '<', date('Y-m-d', time())],
            ['is_expired', '=', false]
        ])
        ->ids();

    $should_be_upcoming_expiry_ids = Subscription::search([
            ['id', 'in', $should_be_updated_ids],
            ['date_to', '<', date('Y-m-d', strtotime('+30 days'))],
            ['has_upcoming_expiry', '=', false]
        ])
        ->ids();

    if(!empty($should_be_expired_ids)) {
        Subscription::ids($should_be_expired_ids)
            ->update(['is_expired' => null]);
    }

    if(!empty($should_be_upcoming_expiry_ids)) {
        Subscription::ids($should_be_upcoming_expiry_ids)
            ->update(['has_upcoming_expiry' => null]);
    }

    $should_have_alert_ids = Subscription::ids($should_be_updated_ids)->get(true);

    foreach($should_have_alert_ids as $subscription_id) {
        eQual::run('do', 'infra_service_check-expiration', ['id' => $subscription_id]);
    }
}
$context->httpResponse()
        ->status(204)
        ->send();
