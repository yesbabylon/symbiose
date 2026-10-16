<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\identity\ContactImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Check the import contacts document data.',
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

/**
 * Action
 */

$import = ContactImport::id($params['id'])
    ->read(['status', 'document_id'])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if($import['status'] !== 'pending') {
    throw new Exception('wrong_status', EQ_ERROR_UNKNOWN);
}
if(!$import['document_id']) {
    throw new Exception('missing_import_document', EQ_ERROR_INVALID_PARAM);
}

$result = [
    'errors'    => 0,
    'logs'      => []
];

$data = [];
try {
    $data = eQual::run('get', 'exchange_import_identity_ContactImport_parse', [
        'id' => $params['id']
    ]);
}
catch(Throwable $e) {
    ++$result['errors'];
    $result['logs'][] = "ERR - {$e->getMessage()}";
}

if($result['errors'] === 0 && empty($data)) {
    ++$result['errors'];
    $result['logs'][] = "ERR - empty import";
}

foreach($data as $index => $item) {
    if(empty($item['firstname'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - missing `firstname`";
    }
    if(empty($item['lastname'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - missing `lastname`";
    }

    $email = $item['email'] ?? '';
    $phone = $item['phone'] ?? '';
    $mobile = $item['mobile'] ?? '';
    if($email === '' && $phone === '' && $mobile === '') {
        ++$result['errors'];
        $result['logs'][] = "ERR - missing contact information at row " . ($index + 2);
    }

    if($phone !== '' && !preg_match('/^\+?[0-9]+$/', $phone)) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `phone` ($phone) at row " . ($index + 2);
    }
    if($mobile !== '' && !preg_match('/^\+?[0-9]+$/', $mobile)) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `mobile` ($mobile) at row " . ($index + 2);
    }
    if($email !== '' && !preg_match('/^([_a-z0-9-\.]+)(\+([_a-z0-9]+))?@(([a-z0-9-]+\.)*)([a-z0-9-]{1,63})(\.[a-z-]{2,24})$/i', $email)) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `email` ($email) at row " . ($index + 2);
    }

    $address_country = $item['address_country'] ?? '';
    if($address_country !== '' && !preg_match('/^[A-Z]{2}$/', $address_country)) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `address_country` ($address_country) at row " . ($index + 2);
    }
}

if($result['errors'] === 0) {
    $result['logs'][] = "INFO- file is valid (no errors found).";
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
