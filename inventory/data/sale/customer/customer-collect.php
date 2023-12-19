<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


use equal\orm\Domain;
use identity\Identity;
use sale\customer\Customer;

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Customer: returns a collection of Reports according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        'entity' =>  [
            'description'   => 'name',
            'type'          => 'string',
            'default'       => 'sale\customer\Customer'
        ],

        'type' => [
            'type'              => 'string',
            'selection'         => [
                'all',
                'I',
                'SE',
                'C',
                'NP',
                'PA'
            ],
            'readonly'          => true,
            'default'           => 'all',                           // has to be changed through type_id
            'description'       => 'Code of the type of identity.'
        ],

        'registration_number' => [
            'type'              => 'string',
            'description'       => 'Organization registration number (company number).',
            'visible'           => [ ['type', '<>', 'I'],['type', '<>', 'all']  ]
        ],

        'legal_name' => [
            'type'              => 'string',
            'description'       => 'Full name of the Identity.',
            'visible'           => [ ['type', '<>', 'I'],['type', '<>', 'all']]
        ],

        'short_name' => [
            'type'          => 'string',
            'description'   => 'Usual name to be used as a memo for identifying the organization (acronym or short name).',
            'visible'           => [ ['type', '<>', 'I'],['type', '<>', 'all']]
        ],

        'citizen_identification' => [
            'type'              => 'string',
            'description'       => 'Citizen registration number, if any.',
            'visible'           => [ ['type', '=', 'I']]
        ],

        'firstname' => [
            'type'              => 'string',
            'description'       => "Full name of the contact (must be a person, not a role).",
            'visible'           => ['type', '=', 'I']
        ],

        'lastname' => [
            'type'              => 'string',
            'description'       => 'Reference contact surname.',
            'visible'           => ['type', '=', 'I']
        ],

        'address' => [
            'type'              => 'string',
            'description'       => "Address the contact"
        ],

        'product_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Product',
            'description'       => 'Product to which the customer.'
        ],

        'software_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\Software',
            'description'       => 'Software to which the customer.'
        ],

        'service_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Service',
            'description'       => 'Service to which the customer.'
        ],

        'subscription_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'inventory\service\Subscription',
            'description'       => 'Customer to which the subscription belongs.',
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




if(isset($params['firstname']) && strlen($params['firstname']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['firstname','ilike','%'.$params['firstname'].'%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);

}


if(isset($params['lastname']) && strlen($params['lastname']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['lastname','ilike','%'.$params['lastname'].'%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}


if(isset($params['short_name']) && strlen($params['short_name']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['short_name','ilike','%'.$params['short_name'].'%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['legal_name']) && strlen($params['legal_name']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['legal_name','ilike','%'.$params['legal_name'].'%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}


if(isset($params['address']) && strlen($params['address']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['address_street','ilike','%'.$params['address'].'%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['citizen_identification']) && strlen($params['citizen_identification']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['citizen_identification','=',$params['citizen_identification']])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['registration_number']) && strlen($params['registration_number']) > 0 ) {
    $identities_ids = [];
    $customers_ids = [];
    $identities_ids = Identity::search(['registration_number','=',$params['registration_number']])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['product_id']) && $params['product_id'] > 0) {
    $customers_ids = [];
    $customers_ids = Customer::search(['products_ids', 'contains', $params['product_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['service_id']) && $params['service_id'] > 0) {
    $customers_ids = [];
    $customers_ids = Customer::search(['services_ids', 'contains', $params['service_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['software_id']) && $params['software_id'] > 0) {
    $customers_ids = [];
    $customers_ids = Customer::search(['softwares_ids', 'contains', $params['software_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

if(isset($params['subscription_id']) && $params['subscription_id'] > 0) {
    $customers_ids = [];
    $customers_ids = Customer::search(['subscriptions_ids', 'contains', $params['subscription_id']])->ids();
    $domain = Domain::conditionAdd($domain, ['id', 'in', $customers_ids]);
}

$params['domain'] = $domain;
$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
