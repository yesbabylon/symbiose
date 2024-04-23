<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Receivables: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'type'              => 'string',
            'description'       => 'Full name (including namespace) of the class to return.',
            'default'           => 'sale\receivable\Receivable'
        ],

        'price_total_min' => [
            'type'              => 'integer',
            'description'       => 'Minimal price for the receivable.'
        ],
        'price_total_max' => [
            'type'              => 'integer',
            'description'       => 'Maximal price for the receivable.'
        ],

        'date_from' => [
            'type'              => 'date',
            'description'       => "Last date of the time interval.",
            'default'           => strtotime("-20 Years")
        ],

        'date_to' => [
            'type'              => 'date',
            'description'       => "First date of the time interval.",
            'default'           => strtotime("+10 Years")
        ],

        'status' => [
            'type'              => 'string',
            'description'       => 'Version of the receivable.',
            'selection'         => ['all','pending', 'invoiced', 'cancelled'],
            'default'           => 'all'
        ],

        'invoice_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\accounting\invoice\Invoice',
            'description'       => 'Invoice the line is related to.'
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

if(isset($params['price_total_min']) && $params['price_total_min'] > 0) {
    $domain = Domain::conditionAdd($domain, ['price', '>=', $params['price_total_min']]);
}

if(isset($params['price_total_max']) && $params['price_total_max'] > 0) {
    $domain = Domain::conditionAdd($domain, ['price', '<=', $params['price_total_max']]);
}

if(isset($params['date_from']) && $params['date_from'] > 0) {
    $domain = Domain::conditionAdd($domain, ['date', '>=', $params['date_from']]);
}

if(isset($params['date_to']) && $params['date_to'] > 0) {
    $domain = Domain::conditionAdd($domain, ['date', '<=', $params['date_to']]);
}

if(isset($params['status']) && strlen($params['status']) > 0 && $params['status']!= 'all') {
    $domain = Domain::conditionAdd($domain, ['status', '=', $params['status']]);
}

if(isset($params['invoice_id']) && $params['invoice_id'] > 0) {
    $domain = Domain::conditionAdd($domain, ['invoice_id', '=', $params['invoice_id']]);
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
