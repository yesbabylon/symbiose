<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Receivables Queues: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'type'              => 'string',
            'description'       => 'Full name (including namespace) of the class to return.',
            'default'           => 'sale\receivable\ReceivablesQueue'
        ],

        'customer_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'sale\customer\Customer',
            'description'       => 'Customer of the subscription.'
        ],

        'has_pending_receivables' => [
            'type'              => 'boolean',
            'description'       => 'Has pending receivables waiting to be invoiced.'
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

if(isset($params['customer_id']) && $params['customer_id'] > 0) {
    $domain[] = ['customer_id', '=', $params['customer_id']];
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

// Filter here because pending_receivables_count is a computed field that is not stored in database
if(isset($params['has_pending_receivables']) && $params['has_pending_receivables']) {
    $result = array_values(
        array_filter(
            $result,
            function($queue) {
                return $queue['pending_receivables_count'] > 0;
            }
        )
    );
}

$context->httpResponse()
        ->body($result)
        ->send();
