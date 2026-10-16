<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use exchange\import\identity\ContactImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Parse a CSV contact import and return normalized contacts.',
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

$stream = fopen('php://temp', 'r+');
if($stream === false) {
    throw new Exception('failed_loading_csv', EQ_ERROR_UNKNOWN);
}

try {
    if(fwrite($stream, $contactImport['document_id']['data']) === false) {
        throw new Exception('Unable to write CSV data to memory stream.');
    }

    rewind($stream);

    $header_row = fgetcsv($stream, null, ',', '"', '');
    if(!is_array($header_row)) {
        throw new Exception('invalid_csv_headers', EQ_ERROR_INVALID_PARAM);
    }

    $column_map = [];
    foreach($header_row as $column => $header) {
        if(!is_scalar($header)) {
            continue;
        }

        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $header));
        $header = strtolower($header);
        if(!in_array($header, $headers, true)) {
            continue;
        }
        if(isset($column_map[$header])) {
            trigger_error("APP::duplicate CSV contact column `{$header}`", EQ_REPORT_WARNING);
            throw new Exception('invalid_csv_headers', EQ_ERROR_INVALID_PARAM);
        }

        $column_map[$header] = $column;
    }

    $has_names = isset($column_map['firstname'], $column_map['lastname']);
    $available_contact_headers = array_intersect($contact_headers, array_keys($column_map));
    if(!$has_names || !count($available_contact_headers)) {
        trigger_error(
            'APP::CSV contact import requires `firstname`, `lastname` and at least one of `email`, `phone` or `mobile`.',
            EQ_REPORT_WARNING
        );
        throw new Exception('invalid_csv_headers', EQ_ERROR_INVALID_PARAM);
    }

    $text_adapter = $adapt->get('txt');

    $mapped_data = [];
    $row_number = 1;
    while(($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
        ++$row_number;
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
                    if(!is_null($value)) {
                        $value = strtoupper(trim((string) $value));
                    }
                    break;
                default:
                    if(!is_null($value)) {
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
            trigger_error(
                "APP::invalid CSV contact at row {$row_number}: `firstname`, `lastname` and at least one contact value are required.",
                EQ_REPORT_WARNING
            );
            throw new Exception('invalid_csv_contact', EQ_ERROR_INVALID_PARAM);
        }

        $mapped_data[] = $record;
    }
}
catch(Throwable $e) {
    if(in_array($e->getMessage(), ['invalid_csv_headers', 'invalid_csv_contact'], true)) {
        throw $e;
    }
    trigger_error("APP::unable to load data from given CSV: " . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('failed_loading_csv', EQ_ERROR_UNKNOWN);
}
finally {
    fclose($stream);
}

$context
    ->httpResponse()
    ->body($mapped_data)
    ->send();
