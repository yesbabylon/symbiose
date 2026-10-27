<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\identity\ContactImport;
use PhpOffice\PhpSpreadsheet\IOFactory;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse an XLSX contact import and return normalized contacts.',
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
    'providers'     => ['context', 'adapt']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\data\adapt\DataAdapterProvider $adapt
 */
['context' => $context, 'adapt' => $adapt] = $providers;

$contactImport = ContactImport::id($params['id'])
    ->read(['document_id' => ['data']])
    ->first();

if(!$contactImport) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}
if(!isset($contactImport['document_id']['data']) || !is_string($contactImport['document_id']['data'])) {
    throw new Exception('missing_import_document', EQ_ERROR_INVALID_PARAM);
}

$headers = [
    'firstname',
    'lastname',
    'email',
    'phone',
    'mobile',
    'address_street',
    'address_dispatch',
    'address_city',
    'address_zip',
    'address_state',
    'address_country'
];
$contact_headers = ['email', 'phone', 'mobile'];

$tmp = null;
try {
    // #todo - when PhpOffice version will support it, use memory stream instead of tmp file
    $reader = IOFactory::createReader('Xlsx');
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    if($tmp === false || file_put_contents($tmp, $contactImport['document_id']['data']) === false) {
        throw new Exception('Unable to write XLSX data to a temporary file.');
    }
    $spreadsheet = $reader->load($tmp);
}
catch(Throwable $e) {
    trigger_error("APP::unable to load data from given XLSX with PhpOffice: " . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('failed_loading_xlsx', EQ_ERROR_UNKNOWN);
}
finally {
    if(is_string($tmp) && file_exists($tmp)) {
        unlink($tmp);
    }
}

$worksheet = $spreadsheet->getActiveSheet();

// Contact imports accept calculated formula results and formatted cell values.
$rows = $worksheet->toArray(null, true, true, true);

$header_row = array_shift($rows);
if(!is_array($header_row)) {
    throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
}

$column_map = [];
foreach($header_row as $column => $header) {
    if(!is_scalar($header)) {
        continue;
    }

    $header = strtolower(trim((string) $header));
    if(!in_array($header, $headers, true)) {
        continue;
    }
    if(isset($column_map[$header])) {
        trigger_error("APP::duplicate XLSX contact column `{$header}`", EQ_REPORT_WARNING);
        throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
    }

    $column_map[$header] = $column;
}

$has_names = isset($column_map['firstname'], $column_map['lastname']);
$available_contact_headers = array_intersect($contact_headers, array_keys($column_map));
if(!$has_names || !count($available_contact_headers)) {
    trigger_error(
        'APP::XLSX contact import requires `firstname`, `lastname` and at least one of `email`, `phone` or `mobile`.',
        EQ_REPORT_WARNING
    );
    throw new Exception('invalid_xlsx_headers', EQ_ERROR_INVALID_PARAM);
}

$text_adapter = $adapt->get('txt');

$mapped_data = [];
foreach($rows as $row_index => $row) {
    $record = array_fill_keys($headers, '');
    foreach($column_map as $key_name => $column) {
        $value = $row[$column] ?? '';

        switch($key_name) {
            case 'email':
                $value = $text_adapter->adaptIn($value, 'email');
                break;
            case 'phone':
            case 'mobile':
                $value = $text_adapter->adaptIn($value, 'phone');
                break;
            case 'address_country':
                if(!empty($value)) {
                    $value = strtoupper(trim((string) $value));
                }
                break;
            default:
                if(!empty($value)) {
                    $value = trim((string) $value);
                }
        }

        $record[$key_name] = $value;
    }

    // Ignore empty rows while preserving values such as "0".
    if(!array_filter($record, fn($value) => $value !== null && $value !== '')) {
        continue;
    }

    $has_contact_value = count(array_filter(
        [$record['email'], $record['phone'], $record['mobile']],
        fn($value) => $value !== null && $value !== ''
    ));
    if($record['firstname'] === '' || $record['lastname'] === '' || !$has_contact_value) {
        $row_number = $row_index + 2;
        trigger_error(
            "APP::invalid XLSX contact at row {$row_number}: `firstname`, `lastname` and at least one contact value are required.",
            EQ_REPORT_WARNING
        );
        throw new Exception('invalid_xlsx_contact', EQ_ERROR_INVALID_PARAM);
    }

    $mapped_data[] = $record;
}

$spreadsheet->disconnectWorksheets();

$context
    ->httpResponse()
    ->body($mapped_data)
    ->send();
