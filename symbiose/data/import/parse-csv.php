<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use symbiose\import\DataImport;

[$params, $providers] = eQual::announce([
    'description'   => 'Parses a CSV data import and returns its content as JSON.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'description'       => 'Identifier of the targeted DataImport object.',
            'foreign_object'    => 'symbiose\import\DataImport',
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

$dataImport = DataImport::id($params['id'])
    ->read(['model', 'document_id' => ['data']])
    ->first();

if(!$dataImport) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!method_exists($dataImport['model'], 'getParseCsvConfig')) {
    throw new Exception('missing_parse_csv_config', EQ_ERROR_INVALID_CONFIG);
}

$parse_csv_config = $dataImport['model']::getParseCsvConfig();
$headers = $parse_csv_config;

if(!is_array($headers) || !$headers) {
    throw new Exception('missing_parse_csv_config', EQ_ERROR_INVALID_CONFIG);
}

$stream = fopen('php://temp', 'r+');
if($stream === false) {
    throw new Exception('failed_loading_csv', EQ_ERROR_UNKNOWN);
}

try {
    $csv_data = $dataImport['document_id']['data'] ?? null;
    if(!is_string($csv_data) || fwrite($stream, $csv_data) === false) {
        throw new Exception('Unable to write CSV data to memory stream.');
    }

    rewind($stream);

    // Skip the CSV header row: the configured headers define the output keys.
    fgetcsv($stream, null, ',', '"', '');

    $mapped_data = [];
    while(($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
        $record = [];
        foreach($headers as $index => $key_name) {
            $record[$key_name] = $row[$index] ?? null;
        }

        // Ignore empty rows while preserving values such as "0".
        if(array_filter($record, fn($value) => $value !== null && $value !== '')) {
            $mapped_data[] = $record;
        }
    }
}
catch(Throwable $e) {
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
