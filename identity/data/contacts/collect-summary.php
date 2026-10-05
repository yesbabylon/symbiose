<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use identity\Identity;

[$params, $providers] = eQual::announce([
    'description' => 'Return contact role counts, incomplete contact counts and slug-based duplicate counts for the Contacts dashboard.',
    'extends'     => 'core_model_collect',
    'params'      => [
        'entity' => [
            'description' => 'Entity represented by the dashboard summary.',
            'type'        => 'string',
            'default'     => 'identity\contacts\collect-summary'
        ],
        'internal_count' => [
            'description' => 'Number of identities with an employee role.',
            'type'        => 'integer',
            'readonly'    => true
        ],
        'customer_count' => [
            'description' => 'Number of identities with a customer role.',
            'type'        => 'integer',
            'readonly'    => true
        ],
        'supplier_count' => [
            'description' => 'Number of identities with a supplier role.',
            'type'        => 'integer',
            'readonly'    => true
        ],
        'total_count' => [
            'description' => 'Total number of contact roles.',
            'type'        => 'integer',
            'readonly'    => true
        ],
        'incomplete_count' => [
            'description' => 'Number of unique contact identities with incomplete details.',
            'type'        => 'integer',
            'readonly'    => true
        ],
        'duplicate_count' => [
            'description' => 'Number of unique contact identities sharing a duplicate slug.',
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

$internal_ids = Identity::search(['employee_id', '<>', null])->ids();
$customer_ids = Identity::search(['customer_id', '<>', null])->ids();
$supplier_ids = Identity::search(['supplier_id', '<>', null])->ids();
$contact_ids = array_values(array_unique(array_merge($internal_ids, $customer_ids, $supplier_ids)));
$role_count = count($internal_ids) + count($customer_ids) + count($supplier_ids);

$incomplete_count = 0;
$slug_counts = [];

if($contact_ids) {
    $contacts = Identity::ids($contact_ids)->read([
        'name',
        'type',
        'firstname',
        'lastname',
        'legal_name',
        'email',
        'phone',
        'mobile',
        'has_vat',
        'vat_number',
        'slug_hash'
    ]);

    foreach($contacts as $contact) {
        $missing_name = empty($contact['name'])
            || ($contact['type'] === 'IN' && (empty($contact['firstname']) || empty($contact['lastname'])))
            || ($contact['type'] !== 'IN' && empty($contact['legal_name']));
        $missing_contact_details = empty($contact['email'])
            && empty($contact['phone'])
            && empty($contact['mobile']);
        $missing_vat = !empty($contact['has_vat']) && empty($contact['vat_number']);

        if($missing_name || $missing_contact_details || $missing_vat) {
            ++$incomplete_count;
        }

        if(!empty($contact['slug_hash'])) {
            $slug_counts[$contact['slug_hash']] = ($slug_counts[$contact['slug_hash']] ?? 0) + 1;
        }
    }
}

$duplicate_count = 0;
foreach($slug_counts as $count) {
    if($count > 1) {
        $duplicate_count += $count;
    }
}

$result = [[
    'id'               => 0,
    'internal_count'   => count($internal_ids),
    'customer_count'   => count($customer_ids),
    'supplier_count'   => count($supplier_ids),
    'total_count'      => $role_count,
    'incomplete_count' => $incomplete_count,
    'duplicate_count'  => $duplicate_count
]];

$context->httpResponse()
    ->header('X-Total-Count', 1)
    ->body($result)
    ->send();
