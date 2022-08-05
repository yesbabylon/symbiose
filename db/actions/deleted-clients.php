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
    'description'   => "Generate CSV files for pricelists, prices, product_models and products based on articles exports from Hestia.",
    'params'        => [
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$file = "packages/db/actions/test.csv";

// $file = glob($path);
if(!file_exists($file)){
    throw new Exception();
}


$ids = [];


$data = loadXlsFile($file);

foreach($data as $row) {

    $date_from_array = date_parse($row['Date_Creation']);

    $date_to_array = date_parse($row['Date_Modif']);     
 
    if(strlen($date_from_array['month']) == 1){
        $month = '0'.$date_from_array['month'];
    }

    if(strlen($date_from_array['day']) == 1){
        $day = '0'.$date_from_array['day'];
    }

    if(strlen($date_to_array['day']) == 1){
        $day_to = '0'.$date_to_array['day'];
    }

    if(strlen($date_to_array['month_to']) == 1){
        $month_to = '0'.$date_to_array['month_to'];
    }

    $date_from = strtotime($date_from_array['year'].'-'.$month.'-'.$day);
    $date_to = strtotime($date_to_array['year'].'-'.$month_to.'-'.$day_to);
    $date_last_import = strtotime('2021-08-03');

    
    if($row['Code_Motif_NPU']){
        array_push($ids, $row['Cle_Client']);
    }
    // if not in db
}
            

$context->httpResponse()
        ->body(['ok'])
        ->send();


function loadXlsFile($file='') {
    $result = [];
    $filetype = IOFactory::identify($file);
    /** @var Reader */
    $reader = IOFactory::createReader($filetype);
    $spreadsheet = $reader->load($file);
    $worksheetData = $reader->listWorksheetInfo($file);

    foreach ($worksheetData as $worksheet) {

        $sheetname = $worksheet['worksheetName'];

        $reader->setLoadSheetsOnly($sheetname);
        $spreadsheet = $reader->load($file);

        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        $header = array_shift($data);

        foreach($data as $raw) {
            $line = array_combine($header, $raw);
            $result[] = $line;
        }
    }
    return $result;
}