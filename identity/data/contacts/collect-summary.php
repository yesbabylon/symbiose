<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Contact;
use purchase\supplier\SupplierContact;
use sale\customer\CustomerContact;

[$params, $providers] = eQual::announce([
    'description' => 'Return contact counts by contact type or related alert category for the Contacts dashboard.',
    'extends'     => 'core_model_collect',
    'params'      => [
        'entity' => [
            'description' => 'Entity represented by the dashboard summary.',
            'type'        => 'string',
            'default'     => 'identity\contacts\collect-summary'
        ],
        'type' => [
            'description' => 'Contact type or related alert category.',
            'type'        => 'string',
            'selection'   => ['internal', 'customers', 'suppliers', 'duplicates', 'incomplete'],
            'readonly'    => true
        ],
        'total' => [
            'description' => 'Number of contacts for the type or alert category.',
            'type'        => 'integer',
            'readonly'    => true
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

$result = [
    [
        'id'    => 1,
        'type'  => 'internal',
        'total' => Contact::search()->count()
    ],
    [
        'id'    => 2,
        'type'  => 'customers',
        'total' => CustomerContact::search()->count()
    ],
    [
        'id'    => 3,
        'type'  => 'suppliers',
        'total' => SupplierContact::search()->count()
    ],
    [
        'id'    => 4,
        'type'  => 'duplicates',
        // Duplicate contacts will be counted from alerts once those alerts are defined.
        'total' => 0
    ],
    [
        'id'    => 5,
        'type'  => 'incomplete',
        // Incomplete contacts will be counted from alerts once those alerts are defined.
        'total' => 0
    ]
];

$context->httpResponse()
    ->header('X-Total-Count', count($result))
    ->body($result)
    ->send();
