<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use equal\orm\Domain;
use inventory\service\Service;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Services: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'inventory\service\Service'
        ],

        'has_subscription' =>[
            'type'              => 'string',
            'description'       => 'The service has a subscription.',
            'selection'         => ['all','yes', 'no']
        ],

        'is_billable' =>[
            'type'              => 'string',
            'description'       => 'The service is billable.',
            'selection'         => ['all','yes', 'no'],
            'visible'           => ['has_subscription','=', 'yes']
        ],

        'is_auto_renew' =>[
            'type'              => 'string',
            'description'       => 'The service is auto renew .',
            'selection'         => ['all','yes', 'no'],
            'visible'           => ['has_subscription','=', 'yes']
        ],

        'customer_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the service.'
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Product to which the service belongs'
        ],

        'service_provider_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\ServiceProvider',
            'description'       => 'The service provider to which the service belongs.',
        ],

        'subscription_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Subscription',
            'description'       => 'Service to which the service belongs.',
            'visible'           => ['has_subscription','=', 'yes']
        ],

        'software_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Software',
            'description'       => 'Software to which the service belongs.'
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
$domain = [];

$YES_OPTION = 'yes';

if(isset($params['has_subscription']) && $params['is_internal']!= 'all') {
    $has_subscription = $params['has_subscription'] == $YES_OPTION  ? true : false;
    $domain = ['has_subscription', '=', $has_subscription];
}

if(isset($params['is_billable']) && $params['is_billable']!= 'all') {
    $is_billable = $params['is_billable'] == $YES_OPTION  ? true : false;
    $domain = ['is_billable', '=', $is_billable];
}

if(isset($params['is_auto_renew']) && $params['is_auto_renew']!= 'all') {
    $is_auto_renew = $params['is_auto_renew'] == $YES_OPTION ? true : false;
    $domain = ['is_auto_renew', '=', $is_auto_renew];
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain = ['product_id', '=', $params['product_id']];
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = ['customer_id', '=', $params['customer_id']];
}

if(isset($params['service_provider_id']) && $params['service_provider_id'] > 0) {
    $domain = ['service_provider_id', '=', $params['service_provider_id']];
}

if(isset($params['software_id']) && $params['software_id'] > 0) {
    $services_ids = Service::search(['softwares_ids', 'contains', $params['software_id']])->ids();
    $domain = ['id', '=', $services_ids];
}

if(isset($params['access_id']) && $params['access_id'] > 0) {
    $services_ids = Service::search(['accesses_ids', 'contains', $params['access_id']])->ids();
    $domain = ['id', '=', $services_ids];
}

if(isset($params['subscription_id']) && $params['subscription_id'] > 0) {
    $services_ids = Service::search(['subscriptions_ids', 'contains', $params['subscription_id']])->ids();
    $domain = ['id', '=', $services_ids];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
