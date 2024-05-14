<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use inventory\service\Service;
use inventory\Software;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Software: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'inventory\Software'
        ],

        'edition' => [
            'type'              => 'string',
            'description'       => "Type of edition (CE/EE/Pro/...)"
        ],

        'version' => [
            'type'              => 'string',
            'description'       => "Installed version of the software"
        ],

        'server_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\server\Server',
            'description'       => 'Server of the software.'
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Service of the software.'
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'The product to which the software.'
        ],

        'subscription_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Subscription',
            'description'       => 'Product to which the software belongs'
        ],

        'instance_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\server\Instance',
            'description'       => 'Instance of the software.'
        ],

        'access_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Access',
            'description'       => 'Access of the software.'
        ],

        'customer_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the software.'
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

if(isset($params['edition']) && strlen($params['edition']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['edition', 'ilike','%'.$params['edition'].'%']);
}

if(isset($params['version']) && strlen($params['version']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['version', 'ilike','%'.$params['version'].'%']);
}

if(isset($params['server_id']) && $params['server_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['server_id', '=', $params['server_id']]);
}

if(isset($params['service_id']) && $params['service_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['service_id', '=', $params['service_id']]);
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['product_id', '=', $params['product_id']]);
}

if(isset($params['subscription_id']) && $params['subscription_id'] > 0) {
    $services_ids = [];
    $services_ids = Service::search(['subscriptions_ids', 'contains', $params['subscription_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['service_id', 'in', $services_ids]);
}

if(isset($params['access_id']) && $params['access_id'] > 0) {
    $softwares_ids = [];
    $softwares_ids = Software::search(['accesses_ids', 'contains', $params['access_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $softwares_ids]);
}

if(isset($params['instance_id']) && $params['instance_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['instance_id', '=', $params['instance_id']]);
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['customer_id', '=', $params['customer_id']]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
