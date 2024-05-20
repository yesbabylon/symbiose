<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Subscriptions: returns a collection of Subscriptions according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'type'              => 'string',
            'description'       => 'Full name (with namespace) of requested entity.',
            'default'           => 'sale\subscription\Subscription'
        ],

        'price_min' => [
            'type'              => 'integer',
            'description'       => 'Minimal price for the subscription.'
        ],

        'price_max' => [
            'type'              => 'integer',
            'description'       => 'Maximal price for the subscription.'
        ],

        'date_from' => [
            'type'              => 'date',
            'description'       => 'Last date of the time interval.',
            'default'           => strtotime('-20 Years')
        ],

        'date_to' => [
            'type'              => 'date',
            'description'       => 'First date of the time interval.',
            'default'           => strtotime('+10 Years')
        ],

        'is_expired' => [
            'type'              => 'string',
            'selection'         => ['all','yes', 'no'],
            'description'       => 'The subscription is expired'
        ],

        'has_upcoming_expiry' => [
            'type'              => 'string',
            'selection'         => ['all','yes', 'no'],
            'description'       => 'The subscription is  up coming expiry.'
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\catalog\Product',
            'description'       => 'Product to which the subscription belongs'
        ],

        'customer_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the subscription.'
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

if(isset($params['price_min']) && $params['price_min'] > 0) {
    $domain[] = ['unit_price', '>=', $params['price_min']];
}

if(isset($params['price_max']) && $params['price_max'] > 0) {
    $domain[] = ['unit_price', '<=', $params['price_max']];
}

if(isset($params['date_from'], $params['date_to']) && $params['date_from'] > 0 && $params['date_to'] > 0) {
    $domain[] = ['date_to', '>=', $params['date_from']];
    $domain[] = ['date_from', '<=', $params['date_to']];
}
else {
    if(isset($params['date_from']) && $params['date_from'] > 0) {
        $domain[] = ['date_from', '>=', $params['date_from']];
    }

    if(isset($params['date_to']) && $params['date_to'] > 0) {
        $domain[] = ['date_to', '<=', $params['date_to']];
    }
}

if(isset($params['is_expired']) && $params['is_expired']!= 'all') {
    $is_expired = $params['is_expired'] == 'yes' ? true : false;
    $domain[] = ['is_expired', '=', $is_expired];
}

if(isset($params['has_upcoming_expiry']) && $params['has_upcoming_expiry']!= 'all') {
    $has_upcoming_expiry = $params['has_upcoming_expiry'] == 'yes' ? true : false;
    $domain[] = ['has_upcoming_expiry', '=', $has_upcoming_expiry];
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain[] = ['product_id', '=', $params['product_id']];
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain[] = ['customer_id', '=', $params['customer_id']];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
