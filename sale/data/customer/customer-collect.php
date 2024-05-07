<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2024
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use identity\Identity;
use sale\customer\Customer;

list($params, $providers) = eQual::announce([
    'description' => 'Advanced search for Customer: returns a collection of Reports according to extra parameters.',
    'extends'     => 'core_model_collect',
    'params'      => [

        'entity' => [
            'type'        => 'string',
            'description' => 'Full name (including namespace) of the class to return.',
            'default'     => 'sale\customer\Customer'
        ],

        'type' => [
            'type'        => 'string',
            'description' => 'Code of the type of identity.',
            'selection'   => ['all', 'I', 'SE', 'C', 'NP', 'PA'],
            'readonly'    => true,
            'default'     => 'all'
        ],

        'registration_number' => [
            'type'        => 'string',
            'description' => 'Organization registration number (company number).',
            'visible'     => [['type', '<>', 'I'], ['type', '<>', 'all']]
        ],

        'legal_name' => [
            'type'        => 'string',
            'description' => 'Full name of the Identity.',
            'visible'     => [['type', '<>', 'I'], ['type', '<>', 'all']]
        ],

        'short_name' => [
            'type'        => 'string',
            'description' => 'Usual name to be used as a memo for identifying the organization (acronym or short name).',
            'visible'     => [['type', '<>', 'I'], ['type', '<>', 'all']]
        ],

        'citizen_identification' => [
            'type'        => 'string',
            'description' => 'Citizen registration number, if any.',
            'visible'     => ['type', '=', 'I']
        ],

        'firstname' => [
            'type'        => 'string',
            'description' => 'Full name of the contact (must be a person, not a role).',
            'visible'     => ['type', '=', 'I']
        ],

        'lastname' => [
            'type'        => 'string',
            'description' => 'Reference contact surname.',
            'visible'     => ['type', '=', 'I']
        ]
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

// Remove filter params of hidden fields
if(!empty($params['type'])) {
    $individual_params = ['firstname', 'lastname', 'citizen_identification'];
    $company_params = ['legal_name', 'short_name', 'registration_number'];

    $hidden_fields = [];
    if($params['type'] === 'all') {
        $hidden_fields = array_merge($individual_params, $company_params);
    }
    else {
        $hidden_fields = $params['type'] === 'I' ? $company_params : $individual_params;
    }

    foreach($hidden_fields as $hidden_field) {
        unset($params[$hidden_field]);
    }
}

$columns = [
    'firstname',
    'lastname',
    'short_name',
    'legal_name',
    'citizen_identification',
    'registration_number'
];

foreach($columns as $column) {
    if(empty($params[$column])) {
        continue;
    }

    $identities_ids = Identity::search([$column, 'ilike', '%' . $params[$column] . '%'])->ids();
    $customers_ids = Customer::search(['partner_identity_id', 'in', $identities_ids])->ids();

    $params['domain'] = Domain::conditionAdd(
        $params['domain'],
        ['id', 'in', $customers_ids]
    );
}

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
