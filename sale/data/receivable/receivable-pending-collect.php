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

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\catalog\Product',
            'description'       => 'The product (SKU) the line relates to.'
        ],

        'customer_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the subscription.'
        ],

        'receivables_queue_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\receivable\ReceivablesQueue',
            'description'       => 'The Queue the receivable is attached to.',
        ]

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context' ]
]);

/** @var \equal\php\Context $context */
list('context' => $context) = $providers;

$domain = [];

if(isset($params['date_from']) && $params['date_from'] > 0) {
    $domain[] = ['date', '>=', $params['date_from']];
}

if(isset($params['date_to']) && $params['date_to'] > 0) {
    $domain[] = ['date', '<=', $params['date_to']];
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $domain[] = ['product_id', '=', $params['product_id']];
}

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain[] = ['customer_id', '=', $params['customer_id']];
}

if(isset($params['receivables_queue_id']) && $params['receivables_queue_id'] > 0) {
    $domain[] = ['receivables_queue_id', '=', $params['receivables_queue_id']];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
