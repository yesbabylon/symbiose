<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use identity\Contact;
use exchange\import\identity\ContactImport;
use identity\Identity;
use identity\IdentityType;

[$params, $providers] = eQual::announce([
    'description'   => 'Import contacts from uploaded file.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'description'       => 'Identifier of the targeted ContactImport object.',
            'foreign_object'    => 'exchange\import\identity\ContactImport',
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

$import = ContactImport::id($params['id'])
    ->read(['status'])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if($import['status'] !== 'ready') {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}

$data = eQual::run('get', 'exchange_import_identity_ContactImport_parse', [
    'id' => $params['id']
]);

$identityType = IdentityType::search(['code', '=', 'IN'])->first();

$result = [
    'created'   => 0,
    'updated'   => 0,
    'errors'    => 0,
    'processed' => 0,
    'logs'      => []
];

foreach($data as $item) {
    $result['processed']++;

    try {
        $identity_values = [
            'type_id'           => $identityType['id'] ?? 1,
            'firstname'         => $item['firstname'],
            'lastname'          => $item['lastname'],
            'email'             => $item['email'] === '' ? null : $item['email'],
            'phone'             => $item['phone'] === '' ? null : $item['phone'],
            'mobile'            => $item['mobile'] === '' ? null : $item['mobile'],
            'address_street'    => $item['address_street'],
            'address_dispatch'  => $item['address_dispatch'],
            'address_city'      => $item['address_city'],
            'address_zip'       => $item['address_zip'],
            'address_state'     => $item['address_state']
        ];
        if($item['address_country'] !== '') {
            $identity_values['address_country'] = $item['address_country'];
        }

        $identity = Identity::create($identity_values)
            ->read(['name'])
            ->first();

        Contact::create([
                'identity_id' => $identity['id']
            ])
            ->first();

        $result['created']++;
        $result['logs'][] = "INFO- created identity id {$identity['id']}";
    }
    catch(Exception $e) {
        $result['errors']++;
        $result['logs'][] = "ERR - unknown error";
        trigger_error('APP::unable to import contact: ' . $e->getMessage(), EQ_REPORT_ERROR);
    }
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
