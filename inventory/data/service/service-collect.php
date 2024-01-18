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
            'default'       => 'inventory\service\Service'
        ],

        'has_subscription' =>[
            'type'              => 'boolean',
            'description'       => 'The service has a subscription .',
            'default'           => false
        ],

        'is_billable' =>[
            'type'              => 'boolean',
            'description'       => 'The service is billable.',
            'default'           => false,
            'visible'           => ["has_subscription","=", true]
        ],

        'is_auto_renew' =>[
            'type'              => 'boolean',
            'description'       => 'The service is auto renew .',
            'default'           => false,
            'visible'           => ["has_subscription","=", true]
        ],

        'is_expired' => [
            'type'              => 'boolean',
            'description'       => 'The subscription is expired.',
            'visible'           => ["has_subscription","=", true]
        ],

        'has_upcoming_expiry' => [
            'type'              => 'boolean',
            'description'       => 'The subscription is  up coming expiry.',
            'visible'           => ["has_subscription","=", true]
        ],

        'is_internal' => [
            'type'              => 'boolean',
            'description'       => 'The service is internal'
        ],

        'customer_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the service.',
            'visible'           => ['is_internal','=', false]
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Product to which the service belongs'
        ],

        'has_external_provider' =>[
            'type'              => 'boolean',
            'description'       => 'The service has external provider.',
        ],

        'service_provider_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\ServiceProvider',
            'description'       => 'The service provider to which the service belongs.',
            'visible'           => ['has_external_provider','=', true]
        ],

        'subscription_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Subscription',
            'description'       => 'Service to which the service belongs.',
            'visible'           => ["has_subscription","=", true]
        ],

        'software_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Software',
            'description'       => 'Software to which the service belongs.'
        ],

        'detail_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Detail',
            'description'       => 'Detail to which the service belongs.'
        ],

        'access_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Access',
            'description'       => 'Access to which the service belongs.'
        ],

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

//   Add conditions to the domain to consider advanced parameters
$domain = $params['domain'];


if(isset($params['has_subscription']) && strlen($params['has_subscription']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['has_subscription', '=', $params['has_subscription']]);
}

if(isset($params['is_billable']) && $params['is_billable'] > 0) {
    $domain = Domain::conditionAdd($domain, ['is_billable', '=', $params['is_billable']]);
}

if(isset($params['is_auto_renew']) && $params['is_auto_renew'] > 0) {
    $domain = Domain::conditionAdd($domain, ['is_auto_renew', '=', $params['is_auto_renew']]);
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['product_id', '=', $params['product_id']]);
}

if(isset($params['is_internal']) && strlen($params['is_internal']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['is_internal', '=', $params['is_internal']]);
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['customer_id', '=', $params['customer_id']]);
}

if(isset($params['has_external_provider']) && strlen($params['has_external_provider']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['has_external_provider', '=', $params['has_external_provider']]);
}
if(isset($params['service_provider_id']) && $params['service_provider_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['service_provider_id', '=', $params['service_provider_id']]);
}

if(isset($params['subscription_id']) && $params['subscription_id'] > 0) {
    $services_ids = [];
    $services_ids = Service::search(['subscriptions_ids', 'contains', $params['subscription_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $services_ids]);
}

if(isset($params['is_expired']) && $params['is_expired'] > 0) {
    $subscriptions_ids=[];
    $services_ids=[];
    $subscriptions_ids = Subscription::search(['is_expired', '=', $params['is_expired']])->ids();
    $services_ids = Service::search(['subscriptions_ids', 'contains', $subscriptions_ids])->ids();
    if(count($services_ids)) {
        $domain = Domain::conditionAdd($domain, ['id', 'in', $services_ids]);
    }
}

if(isset($params['has_upcoming_expiry']) && $params['has_upcoming_expiry'] > 0) {
    $subscriptions_ids=[];
    $services_ids=[];
    $subscriptions_ids = Subscription::search(['has_upcoming_expiry', '=', $params['has_upcoming_expiry']])->ids();
    $services_ids = Service::search(['subscriptions_ids', 'contains', $subscriptions_ids])->ids();
    if(count($services_ids)) {
        $domain = Domain::conditionAdd($domain, ['id', 'in', $services_ids]);
    }
}

if(isset($params['software_id']) && $params['software_id'] > 0) {
    $services_ids = [];
    $services_ids = Service::search(['softwares_ids', 'contains', $params['software_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $services_ids]);
}

if(isset($params['detail_id']) && $params['detail_id'] > 0) {
    $services_ids = [];
    $services_ids = Service::search(['details_ids', 'contains', $params['detail_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $services_ids]);
}

if(isset($params['access_id']) && $params['access_id'] > 0) {
    $services_ids = [];
    $services_ids = Service::search(['accesses_ids', 'contains', $params['access_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $services_ids]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
