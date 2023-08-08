<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use equal\orm\Domain;
use inventory\Product;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Products: returns a collection of Reports according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'inventory\Product'
        ],

        'is_internal' =>[
            'type'              => 'boolean',
            'description'       => 'The product is internal.',
            'default'           => false
        ],

        'customer_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\sale\customer\Customer',
            'description'       => 'Customer of the product.',
            'visible'           => ['is_internal','=', false]
        ],

        'server_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\server\Server',
            'description'       => 'Server used by product.',
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Services used by product.',
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


if(isset($params['is_internal']) && strlen($params['is_internal']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['is_internal', '=', $params['is_internal']]);
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['customer_id', '=', $params['customer_id']]);
}

if(isset($params['server_id']) && $params['server_id'] > 0) {
    $products_ids = [];
    $products_ids = Product::search(['servers_ids', 'contains', $params['server_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $products_ids]);
}

if(isset($params['service_id']) && $params['service_id'] > 0) {
    $products_ids = [];
    $products_ids = Product::search(['services_ids', 'contains', $params['service_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', '=', $products_ids]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
