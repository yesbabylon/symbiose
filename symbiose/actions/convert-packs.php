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
    'description'   => "Generate CSV files for packs and pack_lines on packs exports from Hestia.",
    'params'        => [
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// charger tous les fichiers R_Packs.csv

$path = "packages/symbiose/test/";


$packs = [];
$pack_lines = [];


// load accounting rules
$products = loadXlsFile($path.'products.csv');

foreach (glob($path."*R_Packs.csv") as $file) {

echo "reading $file".PHP_EOL;

    $path_parts = pathinfo($file);
    $filetype = IOFactory::identify($file);

    $filename = $path_parts['filename'];

    $nomenc_lines = loadXlsFile($path.$filename.'_Nomenc.csv');


    // read each pack

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

// parcourir le fichier des lignes
        foreach($data as $raw) {


            $line = array_combine($header, $raw);


            $centers_map = [
                'GG' => [
                    'prefix_list'           => ['AP', 'AE','BA','BG','BO','BP','BR','CH','CO','DA','HA','HU','LA','LO','LP','LS','MA','MO','VE','WC','WE','GG', ''],
                ],
                'GA' => [
                    'prefix_list'           => ['EU', 'HL', 'OV', 'LO', 'RO', 'WA', 'GA', ''],
                ],
                'VS' => [
                    'prefix_list'           => ['VS', 'GA', 'GG', ''],
                ],
            ];

            /*
                resolve center prefix
            */
            $center_prefix = '';

            if(strpos($filename, 'GG_') !== false) {
                $center_prefix = 'GG';
            }
            else if(strpos($filename, 'GA_') !== false) {
                $center_prefix = 'GA';
            }
            else if(strpos($filename, 'VS_') !== false) {
                $center_prefix = 'VS';
            }


            $prefixes = $centers_map[$center_prefix]['prefix_list'];

            // l'id du pack est arbitraire : on ne l'utilise que pour retrouver les lignes
            $pack_id = $line['Numéro_Pack'];


            /*
            => retrouver le produit en fonction
                - du centre
                - de la tranche d'âge du produit correspondant au pack (sur base du Codif_Mnémo_Pack - pour le pack produit; et Codif_Mnémo_Nomenc pour le produit/ligne de pack)
             */
            // remove leading §§~_
            $pack_code = substr($line['Codif_Mnémo_Pack'], 6);
            $pack_code = str_replace(['à', 'ï', 'î', 'é', 'ê','è', 'ë', 'û'], ['a', 'i', 'i', 'e', 'e', 'e', 'e', 'u'], $pack_code);
            
            // retrieve pack from the list of products (the same pack might refer to distinct products across the centers)
// echo "looking for {$pack_code}".PHP_EOL;
            $matches = [];
            foreach($products as $product_index => $product) {
                if(stripos($product['sku'], $pack_code) !== false) {
                    foreach($prefixes as $prefix_index => $prefix) {
                        if(strlen($prefix)) {
                            $prefix .= '-';
                        }
                        if(stripos($product['sku'], $prefix.$pack_code) === 0) {
                            if( strlen($product['sku']) == strlen($prefix.$pack_code) || stripos($product['sku'], $prefix.$pack_code.'-') === 0) {
                                $matches[] = $product_index;
                            }
                        }
                    }
                }
            }

            if(empty($matches)) {
                die($center_prefix.' : no value found for pack '.$pack_code);
            }
/*
echo "found ";
foreach($matches as $index) {
    echo $products[$index]['sku'].',';
}
echo PHP_EOL;
*/
            foreach($matches as $index) {

                // product is a pack
                $product = $products[$index];
                $product_parts = explode('-', $product['sku']);
                if(count($product_parts) == 2) {
                    $product_category = '';
                    list($product_code, $product_option) = $product_parts;
                }
                else {
                    list($product_category, $product_code, $product_option) = $product_parts;
                }

// echo "pack {$product['sku']}".PHP_EOL;
                /*

                dans le fichier _R_Packs_nomenc correspondant, prendre toutes les lignes  avec Numéro_Pack correspondant

                utiliser Codif_Mnémo_Nomenc pour retrouver le product correspondant
                il doit y avoir un match au niveau du suffixe
                */

                foreach($nomenc_lines as $nomenc_line) {
                    if($pack_id == $nomenc_line['Numéro_Pack']) {
                        $nomenc_code = substr($nomenc_line['Codif_Mnémo_Nomenc'], 5);
                        $nomenc_code = str_replace(['à', 'ï', 'î', 'é', 'ê','è', 'ë', 'û'], ['a', 'i', 'i', 'e', 'e', 'e', 'e', 'u'], $nomenc_code);

                        $subproduct_id = 0;

                        $subprefixes = [];
                        if($product_category == '') {
                            $subprefixes = ['', 'GG', 'GA', 'VS'];
                        }
                        else {
                            $subprefixes = [$product_category, ''];
                        }
                        foreach($subprefixes as $prefix) {

                            if(strlen($prefix)) {
                                $prefix .= '-';
                            }
                            foreach([$product_option, 'A'] as $test_option) {
                                $subproduct_sku = $prefix.$nomenc_code.'-'.$test_option;

                                // echo "looking for $subproduct_sku".PHP_EOL;
                                foreach($products as $subproduct) {
                                    if(strcasecmp($subproduct['sku'], $subproduct_sku) == 0) {
                                        $subproduct_id = $subproduct['id'];
                                        break 3;
                                    }
                                }
                            }
                        }


                        if(!$subproduct_id) {
                            // ignore product
                            echo($product['sku'].' : no match for subproduct '.$nomenc_code.PHP_EOL);
                            // continue 2;
                            $subproduct_sku = $nomenc_code;
                        }

                            // create  pack_line

                            // echo "  found match {$nomenc_code} : {$subproduct_sku}".PHP_EOL;
                            $pack_line_id = count($pack_lines) + 1;
                            $pack_lines[] = [
                                'id'                => $pack_line_id,
                                'parent_product_id' => $product['id'],
                                'child_product_id'  => $subproduct_id,
                                'parent_sku'        => $product['sku'],
                                'child_sku'         => $subproduct_sku
                            ];


                    }
                }
            }



        }
    }

}

if(count($pack_lines)) {
    $data = [];
    $first = $pack_lines[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($pack_lines as $pack_line) {
        $data[] = implode(';', array_values($pack_line));
    }
    file_put_contents($path."pack_lines.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}




$context->httpResponse()
        ->body([])
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