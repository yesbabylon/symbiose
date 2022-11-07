<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;


use core\User;

list($params, $providers) = announce([
    'description'   => "Convert XLS file dedicated to import to their JSON equivalent.",
    'params'        => [
    ],
    'constants'     => ['DEFAULT_LANG'],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];



$path = "packages/symbiose/init/";

foreach (glob($path."*.xls") as $filename) {
    $result = [];

    $path_parts = pathinfo($filename);

    $entity = str_replace('_', '\\', $path_parts['filename']);
    $model = $orm->getModel($entity);
    $schema = $model->getSchema();

    $filetype = IOFactory::identify($filename);

    /** @var Reader */
    $reader = IOFactory::createReader($filetype);

    $spreadsheet = $reader->load($filename);
    $worksheetData = $reader->listWorksheetInfo($filename);

    foreach ($worksheetData as $worksheet) {

        $sheetname = $worksheet['worksheetName'];

        $reader->setLoadSheetsOnly($sheetname);
        $spreadsheet = $reader->load($filename);

        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        $header = array_shift($data);

        $objects = [];

        foreach($data as $index => $raw) {

            $line = array_combine($header, $raw);

            // make sure the lang is define in the line map
            if(!isset($line['lang'])) {
                $line['lang'] = constant('DEFAULT_LANG');
            }

            // clean up the line
            $values = $line;
            foreach($values as $field => $value) {

                if($field != 'lang' && !isset($schema[$field]['type'])) {
                    echo "$entity : malformed schema for field $field".PHP_EOL;
                    continue;
                }

                if(!in_array($schema[$field]['type'], ['boolean', 'integer', 'float']) && (empty($value) || $field == 'lang')) {
                    unset($values[$field]);
                    continue;
                }

                // adapt dates
                if($schema[$field]['type'] == 'date') {
                    $date_parts = explode('/', $value);
                    if(count($date_parts) < 3) {
                        echo "$entity : malformed date for field $field at index $index".PHP_EOL;
                    }
                    $values[$field] = sprintf("%04d-%02d-%02d", $date_parts[2], $date_parts[0], $date_parts[1]);
                }

            }
            if(!isset($objects[ $line['lang'] ])) {
                $objects[ $line['lang'] ] = [];
            }
            $objects[ $line['lang'] ][] = $values;

        }

        foreach($objects as $lang => $values) {
            $result[] = [
                "name" => $entity,
                "lang" => $lang,
                "data" => $values
            ];
        }


    }

    $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($path_parts['dirname'].'/'.$path_parts['filename'].'.json', $json);

}

$context->httpResponse()
        ->body([])
        ->send();