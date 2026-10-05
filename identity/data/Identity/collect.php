<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use identity\Identity;

[$params, $providers] = eQual::announce([
    'description' => 'Search identities across person, organization and contact details.',
    'extends'     => 'core_model_collect',
    'params'      => [
        'entity' => [
            'description' => 'Full name of the entity to collect.',
            'type'        => 'string',
            'default'     => 'identity\Identity'
        ],
        'query' => [
            'description' => 'Person, organization, email, phone or VAT number to search for.',
            'type'        => 'string'
        ]
    ],
    'response' => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context']
]);

['context' => $context] = $providers;

if(!empty($params['query'])) {
    $query = '%' . trim($params['query']) . '%';
    $identity_ids = [];

    foreach([
        'name',
        'firstname',
        'lastname',
        'legal_name',
        'short_name',
        'email',
        'phone',
        'mobile',
        'vat_number'
    ] as $field) {
        $identity_ids = array_merge(
            $identity_ids,
            Identity::search([$field, 'ilike', $query])->ids()
        );
    }

    $params['domain'] = Domain::conditionAdd(
        $params['domain'],
        ['id', 'in', array_values(array_unique($identity_ids))]
    );
}

$result = eQual::run('get', 'core_model_collect', $params, true);

$context->httpResponse()
    ->body($result)
    ->send();
