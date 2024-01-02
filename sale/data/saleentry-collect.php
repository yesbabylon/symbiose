<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use equal\orm\Domain;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Receivables: returns a collection of Reports according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'       => 'name',
            'type'              => 'string',
            'default'           => 'sale\SaleEntry'
        ],

        'is_billable' => [
            'type'              => 'boolean',
            'description'       => 'Can be billed to the customer.',
            'default'           => false
        ],

        'has_receivable' => [
            'type'              => 'boolean',
            'description'       => 'The entry is linked to a receivable entry.',
            'default'           => false
        ],

        'receivable_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\receivable\Receivable',
            'description'       => 'The receivable entry the sale entry is linked to.',
            'visible'           => ['has_receivable', '=', true]
        ],

        'product_id'=> [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\catalog\Product',
            'description'       => 'The product (SKU) the line relates to.'
        ],

        'customer_id'=> [
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
    'providers'     => [ 'context', 'orm' ]
]);
/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];

//   Add conditions to the domain to consider advanced parameters
$domain = $params['domain'];

if(isset($params['is_billable']) && strlen($params['is_billable']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['is_billable', '=', $params['is_billable']]);
}

if(isset($params['has_receivable']) && strlen($params['has_receivable']) > 0 ) {
    $domain = Domain::conditionAdd($domain, ['has_receivable', '=', $params['has_receivable']]);
}

if(isset($params['receivable_id']) && $params['receivable_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['receivable_id', '=', $params['receivable_id']]);
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['product_id', '=', $params['product_id']]);
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['customer_id', '=', $params['customer_id']]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
