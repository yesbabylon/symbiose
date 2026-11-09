<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use inventory\service\Service;

[$params, $providers] = eQual::announce([
    'description' => 'Return service subscriptions matching operational lifecycle and service filters.',
    'extends'     => 'core_model_collect',
    'params'      => [
        'entity' => [
            'type'        => 'string',
            'description' => 'Full name of the requested entity.',
            'default'     => 'infra\service\Subscription'
        ],
        'date_from' => [
            'type'        => 'date',
            'description' => 'First date of the subscription overlap interval.',
            'default'     => strtotime('-20 years')
        ],
        'date_to' => [
            'type'        => 'date',
            'description' => 'Last date of the subscription overlap interval.',
            'default'     => strtotime('+10 years')
        ],
        'is_expired' => [
            'type'        => 'string',
            'selection'   => ['all', 'yes', 'no'],
            'default'     => 'all',
            'description' => 'Filter subscriptions by expiration state.'
        ],
        'has_upcoming_expiry' => [
            'type'        => 'string',
            'selection'   => ['all', 'yes', 'no'],
            'default'     => 'all',
            'description' => 'Filter subscriptions by upcoming expiration state.'
        ],
        'service_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\service\Service',
            'description'    => 'Service to which the subscription belongs.'
        ],
        'inventory_product_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\Product',
            'description'    => 'Infrastructure product to which the subscription belongs.'
        ],
        'customer_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'sale\customer\Customer',
            'description'    => 'Customer associated with the subscribed service.'
        ]
    ],
    'response' => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$domain = [];

if(isset($params['date_from'], $params['date_to'])) {
    $domain[] = ['date_to', '>=', $params['date_from']];
    $domain[] = ['date_from', '<=', $params['date_to']];
}
elseif(isset($params['date_from'])) {
    $domain[] = ['date_to', '>=', $params['date_from']];
}
elseif(isset($params['date_to'])) {
    $domain[] = ['date_from', '<=', $params['date_to']];
}

if(($params['is_expired'] ?? 'all') !== 'all') {
    $domain[] = ['is_expired', '=', $params['is_expired'] === 'yes'];
}

if(($params['has_upcoming_expiry'] ?? 'all') !== 'all') {
    $domain[] = ['has_upcoming_expiry', '=', $params['has_upcoming_expiry'] === 'yes'];
}

if(!empty($params['service_id'])) {
    $domain[] = ['service_id', '=', $params['service_id']];
}

if(!empty($params['inventory_product_id'])) {
    $service_ids = Service::search(['product_id', '=', $params['inventory_product_id']])->ids();
    $domain[] = ['service_id', 'in', $service_ids];
}

if(!empty($params['customer_id'])) {
    $domain[] = ['customer_id', '=', $params['customer_id']];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'core_model_collect', $params, true);

$context->httpResponse()
    ->body($result)
    ->send();
