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
            'foreign_object'    => 'identity\ContactImport'
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
    ->read(['status', 'document_id' => ['content_type']])
    ->first();

if(!$import) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($import['status'], ['doc_uploaded', 'check_failed'])) {
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

$result = [
    'errors'    => 0,
    'logs'      => []
];

if($parse_action === 'parse-xls') {
    if(count($data) > 1) {
        ++$result['errors'];
        $result['logs'][] = "ERR - XLSX import document must only contain one sheet";
    }

    // Set XLSX default sheet data in data to match csv
    $data = $data['default'];
}

if(empty($data)) {
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
    if(!preg_match('/^\+?[0-9]*$/', $item['phone'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `phone` ({$item['phone']}) at row " . ($index + 2);
    }
    if(!preg_match('/^\+?[0-9]*$/', $item['mobile'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `mobile` ({$item['mobile']}) at row " . ($index + 2);
    }
    if(strlen($item['email']) > 0 && !preg_match('/^([_a-z0-9-\.]+)(\+([_a-z0-9]+))?@(([a-z0-9-]+\.)*)([a-z0-9-]{1,63})(\.[a-z-]{2,24})$/i', $item['email'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `email` ({$item['email']}) at row " . ($index + 2);
    }
    if(!preg_match('/^[A-Z]{2}$/', $item['address_country'])) {
        ++$result['errors'];
        $result['logs'][] = "ERR - invalid `address_country` ({$item['address_country']}) at row " . ($index + 2);
    }
}

if($result['errors'] === 0) {
    $result['logs'][] = "INFO- file is valid (no errors found).";
}

ContactImport::id($import['id'])->update([
    'logs' => json_encode($result['logs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
]);

if($result['errors'] === 0) {
    ContactImport::id($import['id'])->transition('mark_checked');
}
elseif($import['status'] !== 'check_failed') {
    ContactImport::id($import['id'])->transition('mark_check_failed');
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
