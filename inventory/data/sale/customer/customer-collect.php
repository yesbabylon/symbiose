<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use sale\customer\Customer;

list($params, $providers) = eQual::announce([
    'description' => 'Advanced search for Customer: returns a collection of Reports according to extra parameters.',
    'extends'     => 'sale_customer_customer-collect',
    'params'      => [

        'product_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\Product',
            'description'    => 'Product to which the customer.'
        ],

        'software_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\server\Software',
            'description'    => 'Software to which the customer.'
        ],

        'service_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\service\Service',
            'description'    => 'Service to which the customer.'
        ],

        'subscription_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'inventory\service\Subscription',
            'description'    => 'Customer to which the subscription belongs.',
        ],

    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

$columns_param_keys_map = [
    'products_ids'      => 'product_id',
    'services_ids'      => 'service_id',
    'softwares_ids'     => 'software_id',
    'subscriptions_ids' => 'subscription_id',
];

foreach($columns_param_keys_map as $column => $param_key) {
    if(empty($params[$param_key])) {
        continue;
    }

    $customers_ids = Customer::search([$column, 'contains', $params[$param_key]])->ids();
    $params['domain'] = Domain::conditionAdd($params['domain'], ['id', 'in', $customers_ids]);
}

$result = eQual::run('get', 'sale_customer_customer-collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
