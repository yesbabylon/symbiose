<?php

use inventory\service\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Update subscriptions expiration columns.',
    'params'      => [],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$should_be_expired_ids = Subscription::search([
    ['date_to', '<', date('Y-m-d', time())],
    ['is_expired', '<>', true]
])
    ->ids();

if (!empty($should_be_expired_ids)) {
    Subscription::ids($should_be_expired_ids)
        ->update(['is_expired' => true]);
}

$should_be_upcoming_expiry_ids = Subscription::search([
    ['date_to', '<', date('Y-m-d', strtotime('+30 days'))],
    ['has_upcoming_expiry', '<>', true]
])
    ->ids();

if (!empty($should_be_upcoming_expiry_ids)) {
    Subscription::ids($should_be_upcoming_expiry_ids)
        ->update(['has_upcoming_expiry' => true]);
}

$context->httpResponse()
        ->status(204)
        ->send();
