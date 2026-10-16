<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\identity\ContactImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse a contact import document and return normalized contacts.',
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
        'content-type'  => 'application/json',
        'charset'       => 'utf-8'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$contactImport = ContactImport::id($params['id'])
    ->read(['document_id' => ['content_type']])
    ->first();

if(!$contactImport) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

$map_parsers = [
    'text/csv'                                                          => 'parse-csv',
    'text/vcard'                                                        => 'parse-vcard',
    'text/x-vcard'                                                      => 'parse-vcard',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'parse-xls'
];

$content_type = $contactImport['document_id']['content_type'] ?? null;
if(!isset($map_parsers[$content_type])) {
    throw new Exception('invalid_import_document_type', EQ_ERROR_INVALID_PARAM);
}

$data = eQual::run(
    'get',
    'exchange_import_identity_ContactImport_' . $map_parsers[$content_type],
    ['id' => $params['id']]
);

$context
    ->httpResponse()
    ->body($data)
    ->send();
