<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use inventory\service\Service;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Subscriptions: returns a collection of Subscriptions according to extra parameters.',
    'extends'       => 'sale_subscription_subscription-collect',
    'params'        => [

        'entity' =>  [
            'type'              => 'string',
            'description'       => 'Full name (with namespace) of requested entity.',
            'default'           => 'sale\subscription\Subscription'
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Service to which the subscription belongs.'
        ],

        'inventory_product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Infrastructure product to which the subscription belongs.'
        ]

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$domain = [];

if(isset($params['service_id']) && $params['service_id'] > 0) {
    $service = Service::id($params['service_id'])
        ->read(['subscription_id'])
        ->first(true);

    $domain[] = ['id', '=', $service['subscription_id'] ?? 0];
}

if(isset($params['inventory_product_id']) && $params['inventory_product_id'] > 0) {
    $services = Service::search(['product_id', '=', $params['inventory_product_id']])
        ->read(['subscription_id'])
        ->get(true);
    $subscriptions_ids = array_values(array_filter(array_map(function($service) {
        return $service['subscription_id'] ?? null;
    }, $services)));
    $domain[] = ['id', 'in', $subscriptions_ids];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'sale_subscription_subscription-collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
