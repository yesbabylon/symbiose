<?php
/*
    Developed by Yesbabylon - https://yesbabylon.com
    (c) 2025-2026 Yesbabylon SA
    Licensed under the GNU AGPL v3 License - https://www.gnu.org/licenses/agpl-3.0.html
*/

use PhpOffice\PhpSpreadsheet\IOFactory;
use symbiose\import\DataImport;

[$params, $providers] = eQual::announce([
    'description'   => "Parses a data import to return its content as JSON.",
    'params'        => [
        'id' =>  [
            'type'              => 'many2one',
            'description'       => "Identifier of the targeted DataImport object.",
            'foreign_object'    => 'symbiose\import\DataImport',
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
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context, 'orm' => $orm] = $providers;

$dataImport = DataImport::id($params['id'])
    ->read(['model', 'name', 'document_id' => ['data']])
    ->first();

if(!$dataImport) {
    throw new Exception('unknown_data_import', EQ_ERROR_UNKNOWN_OBJECT);
}

if(!method_exists($dataImport['model'], 'getParseXlsConfig')) {
    throw new Exception('missing_parse_xls_config', EQ_ERROR_INVALID_CONFIG);
}

$parse_xls_config = $dataImport['model']::getParseXlsConfig();

try {
    // #todo - when PhpOffice version will support it, use memory stream instead of tmp file
    $reader = IOFactory::createReader('Xlsx');
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    file_put_contents($tmp, $dataImport['document_id']['data']);
    $spreadsheet = $reader->load($tmp);
    unlink($tmp);
}
catch(Exception $e) {
    trigger_error("APP::unable to load data from given XLSX with PhpOffice: " . $e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('failed_loading_xlsx', EQ_ERROR_UNKNOWN);
}

$result = [];
foreach($spreadsheet->getWorksheetIterator() as $worksheet) {
    $sheet_name = trim($worksheet->getTitle());

    $headers = $parse_xls_config[$sheet_name] ?? null;
    if(!$headers) {
        $sheet_name = 'default';
        $headers = $parse_xls_config[$sheet_name] ?? null;
    }
    if(!$headers) {
        continue;
    }

    $rows = $worksheet->toArray(null, true, true, true);

    // skip first line
    array_shift($rows);

    $mapped_data = [];
    foreach($rows as $row) {
        $record = [];
        foreach($headers as $index => $key_name) {
            $col = chr(65 + $index);
            $value = $row[$col] ?? null;
            $record[$key_name] = $row[$col];
        }
        // ignore empty rows
        if(array_filter($record)) {
            $mapped_data[] = $record;
        }
    }

    $result[$sheet_name] = $mapped_data;
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
