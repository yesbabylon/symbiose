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
    'description' => 'Advanced search for Customer: returns a collection of Reports according to extra parameters.',
    'extends'     => 'core_model_collect',
    'params'      => [

        'entity' => [
            'type'        => 'string',
            'default'     => 'sale\customer\Customer',
            'description' => 'Full name (including namespace) of the class to return.'
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
        ],

        'address' => [
            'type'        => 'string',
            'description' => 'Address the contact'
        ],
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

// Remove filter params of hidden fields
if (!empty($params['type'])) {
    $individualParams = ['firstname', 'lastname', 'citizen_identification'];
    $companyParams = ['legal_name', 'short_name', 'registration_number'];

    $hiddenFields = [];
    if ($params['type'] === 'all') {
        $hiddenFields = array_merge($individualParams, $companyParams);
    } else {
        $hiddenFields = $params['type'] === 'I' ? $companyParams : $individualParams;
    }

    foreach ($hiddenFields as $hiddenField) {
        unset($params[$hiddenField]);
    }
}

$columns = [
    'firstname',
    'lastname',
    'short_name',
    'legal_name',
    'address',
    'citizen_identification',
    'registration_number'
];

foreach ($columns as $column) {
    if (empty($params[$column])) {
        continue;
    }

    $identitiesIds = Identity::search([$column, 'ilike', '%' . $params[$column] . '%'])->ids();
    $customersIds = Customer::search(['partner_identity_id', 'in', $identitiesIds])->ids();

    $params['domain'] = Domain::conditionAdd(
        $params['domain'],
        ['id', 'in', $customersIds]
    );
}

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
