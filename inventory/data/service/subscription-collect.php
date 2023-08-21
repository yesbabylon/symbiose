<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use equal\orm\Domain;
use inventory\Product;
use inventory\service\Subscription;
use inventory\service\Service;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Accesses: returns a collection of Reports according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'inventory\service\Subscription'
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
            'type'          => 'date',
            'description'   => "Last date of the time interval.",
            'default'       => strtotime("-20 Years")
        ],

        'date_to' => [
            'type'          => 'date',
            'description'   => "First date of the time interval.",
            'default'       => strtotime("+10 Years")
        ],

        'is_expired' => [
            'type'              => 'boolean',
            'description'       => 'The subscription is expired.',
            'default'           => false
        ],

        'has_upcoming_expiry' => [
            'type'              => 'boolean',
            'description'       => 'The subscription is  up coming expiry.',
            'default'           => false
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Service to which the subscription belongs.'
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Product to which the subscription belongs'
        ],

        'product_identifier' => [
            'type'              => 'integer',
            'description'       => 'Numeric Product identifier to which the service belongs'
        ],

        'catalog_product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\sale\catalog\Product',
            'description'       => 'Product of the catalog sale.'
        ],

        'customer_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\sale\customer\Customer',
            'description'       => 'Customer of the subscription.'
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);
/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];



//   Add conditions to the domain to consider advanced parameters
$domain = $params['domain'];

if(isset($params['price_min']) && $params['price_min'] > 0) {
    $domain = Domain::conditionAdd($domain, ['price', '>=', $params['price_min']]);
}

if(isset($params['price_max']) && $params['price_max'] > 0) {
    $domain = Domain::conditionAdd($domain, ['price', '<=', $params['price_max']]);
}

if(isset($params['date_from']) && $params['date_from'] > 0) {
    $domain = Domain::conditionAdd($domain, ['date_from', '>=', $params['date_from']]);
}

if(isset($params['date_to']) && $params['date_to'] > 0) {
    $domain = Domain::conditionAdd($domain, ['date_to', '<=', $params['date_to']]);
}

if(isset($params['is_expired']) && strlen($params['is_expired']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['is_expired', '=', $params['is_expired']]);
}

if(isset($params['has_upcoming_expiry']) && $params['has_upcoming_expiry'] > 0) {
    $domain = Domain::conditionAdd($domain, ['has_upcoming_expiry', '=', $params['has_upcoming_expiry']]);
}


if(isset($params['service_id']) && $params['service_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['service_id', '=', $params['service_id']]);
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $services_ids = [];
    $subscriptions_ids = [];
    $services_ids = Service::search(['product_id', 'in', $params['product_id']])->ids();
    $subscriptions_ids = Subscription::search(['service_id', 'in', $services_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $subscriptions_ids]);
}

if(isset($params['product_identifier']) && $params['product_identifier'] > 0) {
    $services_ids = [];
    $subscriptions_ids = [];
    $services_ids = Service::search(['product_id', 'in', $params['product_identifier']])->ids();
    $subscriptions_ids = Subscription::search(['service_id', 'in', $services_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $subscriptions_ids]);
}



if(isset($params['catalog_product_id']) && $params['catalog_product_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['product_id', '=', $params['catalog_product_id']]);
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['customer_id', '=', $params['customer_id']]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
