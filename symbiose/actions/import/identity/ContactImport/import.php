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
        'id' => [
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
    ->read(['status', 'logs', 'document_id' => ['content_type']])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($import['status'], ['checked', 'import_failed'])) {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}

$map_parse_actions = [
    'text/csv'                                                          => 'parse-csv',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'parse-xls'
];

if(!isset($map_parse_actions[$import['document_id']['content_type']])) {
    throw new Exception('invalid_import_document_type', EQ_ERROR_INVALID_PARAM);
}

$parse_action = $map_parse_actions[$import['document_id']['content_type']];

$data = eQual::run('get', "symbiose_import_$parse_action", [
    'id' => $params['id']
]);

if($parse_action === 'parse-xls') {
    // handle xls sheet
    $data = $data['default'];
}

$identity_type_individual = IdentityType::search(['code', '=', 'IN'])->first();

$result = [
    'created'   => 0,
    'updated'   => 0,
    'errors'    => 0,
    'processed' => 0,
    'logs'      => []
];

$created_items = [
    'identities_ids'    => [],
    'contacts_ids'      => []
];

$log = [];

$success = false;

foreach($data as $item) {
    $result['processed']++;

    try {
        $base_domain = [
            ['type_id', '=', $identity_type_individual['id']],
            ['firstname', 'ilike', trim($item['firstname'])],
            ['lastname', 'ilike', trim($item['lastname'])]
        ];

        $domain = [];
        if(!empty(trim($item['email']))) {
            $domain[] = array_merge($base_domain, [
                ['email', '=', trim($item['email'])]
            ]);
        }
        elseif(!empty($formatPhone($item['phone']))) {
            $domain[] = array_merge($base_domain, [
                ['phone', '=', $formatPhone($item['phone'])]
            ]);
        }
        elseif(!empty($formatPhone($item['mobile']))) {
            $domain[] = array_merge($base_domain, [
                ['mobile', '=', $formatPhone($item['mobile'])]
            ]);
        }

        $identity = null;
        if(!empty($domain)) {
            $identity = Identity::search($domain)->first();
        }

        if(!$identity) {
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

            $contact = Contact::create([
                'identity_id' => $identity['id']
            ])
                ->first();

            $result['created']++;
        }
        else {
            Identity::id($identity['id'])
                ->update([
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

            $contact = Contact::search(['identity_id', '=', $identity['id']])->first();
            if(!$contact) {
                $contact = Contact::create([
                    'identity_id' => $identity['id']
                ])
                    ->first();

                $result['created']++;
            }
            else {
                $result['updated']++;
            }
        }
    }
    catch(Exception $e) {
        $result['errors']++;
        $result['logs'][] = "ERR - {$e->getMessage()}";
    }
}

$logs = json_decode($import['logs'], true);
ContactImport::id($import['id'])->update([
    'logs' => json_encode(array_merge($logs, $result['logs']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
]);

if($result['errors'] === 0) {
    ContactImport::id($import['id'])->transition('mark_imported');
}
elseif($import['status'] !== 'import_failed') {
    ContactImport::id($import['id'])->transition('mark_import_failed');
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
