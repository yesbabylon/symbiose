<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use identity\Contact;
use identity\Identity;
use identity\IdentityType;
use symbiose\import\identity\ContactImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Import contacts from uploaded file.',
    'params'        => [
        'id' =>  [
            'type'              => 'many2one',
            'description'       => 'Identifier of the targeted ContactImport object.',
            'foreign_object'    => 'symbiose\import\identity\ContactImport',
            'required'          => true
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/json'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$formatPhone = function($value) {
    $phone = preg_replace('/[^0-9+]/', '', $value);
    if(empty($phone)) {
        $phone = null;
    }

    return $phone;
};

$import = ContactImport::id($params['id'])
    ->read(['document_id' => ['content_type']])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

$map_parse_actions = [
    'text/csv'                                                          => 'parse-csv',
    'text/plain'                                                        => 'parse-csv',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'parse-xls'
];

if(!isset($map_parse_actions[$import['document_id']['content_type']])) {
    throw new Exception('invalid_import_document_type', EQ_ERROR_INVALID_PARAM);
}

$parse_action = $map_parse_actions[$import['document_id']['content_type']];

$data = eQual::run('get', "symbiose_import_$parse_action", [
    'id' => $params['id']
]);

if($parse_action === 'parse-csv') {
    // If CSV file set all data as default sheet to harmonize with XLSX document parsing
    $data = [
        'default' => $data
    ];
}

file_put_contents(QN_LOG_STORAGE_DIR.'/tmp.log', json_encode($data).PHP_EOL, FILE_APPEND | LOCK_EX);

$identity_type_individual = IdentityType::search(['code', '=', 'IN'])->first();

$created_items = [
    'identities_ids'    => [],
    'contacts_ids'      => []
];

$log = [];

$success = false;
try {
    foreach($data['default'] as $item) {
        $identity = Identity::create([
            'type_id'           => $identity_type_individual['id'],
            'firstname'         => trim($item['firstname']),
            'lastname'          => trim($item['lastname']),
            'email'             => trim($item['email']),
            'phone'             => $formatPhone($item['phone']),
            'mobile'            => $formatPhone($item['mobile']),
            'address_street'    => trim($item['address_street']),
            'address_dispatch'  => trim($item['address_dispatch']),
            'address_city'      => trim($item['address_city']),
            'address_zip'       => trim($item['address_zip']),
            'address_state'     => trim($item['address_state']),
            'address_country'   => trim($item['address_country'])
        ])
            ->read(['name'])
            ->first();

        $created_items['identities_ids'][] = $identity['id'];

        $contact = Contact::create([
            'identity_id' => $identity['id']
        ])
            ->first();

        $created_items['contacts_ids'][] = $contact['id'];
    }

    $success = true;
}
catch(Exception $e) {
    Identity::ids($created_items['identities_ids'])->delete(true);
    Contact::ids($created_items['contacts_ids'])->delete(true);
}

if($success) {
    ContactImport::id($import['id'])->transition('mark_imported');
}
else {
    ContactImport::id($import['id'])->transition('mark_import_failed');

    throw new Exception('import_failed', EQ_ERROR_UNKNOWN);
}

$context
    ->httpResponse()
    ->send();
