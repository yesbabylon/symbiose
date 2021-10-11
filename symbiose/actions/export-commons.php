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
    'description'   => "Returns a view populated with a collection of objects, and outputs it as an XLS spreadsheet.",
    'params'        => [
    ],
    'response'      => [
        'accept-origin' => '*'        
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// charger tous les fichiers R_Articles.csv

$path = "packages/symbiose/test/";

$objects = [];
foreach (glob($path."*R_Articles.csv") as $filename) {
    


    $path_parts = pathinfo($filename);

    $entity = str_replace('_', '\\', $path_parts['filename']);

    $filetype = IOFactory::identify($filename);


    $objects[$entity] = [];
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

        
        foreach($data as $raw) {
            $line = array_combine($header, $raw);
            // clean up the line
            foreach($line as $field => $value) {
                $line[$field] = trim($value);
            }

            $objects[$entity][] = $line;            
        }
    }
}

$GA_entities = [
    'HanL\R\Articles',
    'Louv\R\Articles',
    'Ovif\R\Articles',
    'Roch\R\Articles',
    'Wann\R\Articles',
    'Vill\R\Articles'
];

$GG_entities = [
    'Vill\R\Articles'
];

$GA_commons = [];
$GG_commons = [];

foreach($objects['Eupe\R\Articles'] as $obj) {
    $code = $obj['Codif_Mnémo'];

    $found = true;
    $results = [];
    foreach($GA_entities as $center) {
        $res = is_present($code, $center, $objects);
        $found = (boolean) $res;
        if(!$found) {
            break;
        }
        $results[] = $res;
    }
    // if present in at least one other GA
    if(count($results)) {
        $GA_commons[$code] = $results;
    }
} 

foreach($objects['GiGr\R\Articles'] as $obj) {
    $code = $obj['Codif_Mnémo'];

    $found = true;
    $results = [];
    foreach($GG_entities as $center) {
        $res = is_present($code, $center, $objects);
        $found = (boolean) $res;
        if(!$found) {
            break;
        }
        $results[] = $res;
    }
    // if present in at least one other GG
    if(count($results)) {
        $GG_commons[$code] = $results;
    }
} 

/*
// check strict match
foreach($GA_commons as $code => $values) {
    echo "$code:";
    $first = array_shift($values);
    foreach($values as $vals) {
        if($vals[0] != $first[0] || $vals[0] != $first[0]) {
            continue 2;
        }
    }
}
*/

$GA_commons = array_keys($GA_commons);
$GG_commons = array_keys($GG_commons);

$Kaleo_commons = array_intersect($GA_commons, $GG_commons);

$GA_commons = array_diff($GA_commons, $Kaleo_commons);
$GG_commons = array_diff($GG_commons, $Kaleo_commons);


file_put_contents($path.'/'.'Kaleo_commons.csv', "\xEF\xBB\xBF".implode(PHP_EOL, $Kaleo_commons));

file_put_contents($path.'/'.'GA_commons.csv', "\xEF\xBB\xBF".implode(PHP_EOL, $GA_commons));

file_put_contents($path.'/'.'GG_commons.csv', "\xEF\xBB\xBF".implode(PHP_EOL, $GG_commons));




$context->httpResponse()
        ->body([])
        ->send();



function is_present($code, $center, $objects) {

    foreach($objects[$center] as $obj) {
        if($code == $obj['Codif_Mnémo']) {
            return [$obj['Tarif_Unitaire'], $obj['ReglCpta']];        
        }
    }

    return false;
}