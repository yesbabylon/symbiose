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

$Kaleo_commons = explode(PHP_EOL, file_get_contents($path.'Kaleo_commons.csv'));
$GG_commons = explode(PHP_EOL, file_get_contents($path.'GG_commons.csv'));
$GA_commons = explode(PHP_EOL, file_get_contents($path.'GA_commons.csv'));


$packs = [];
$pack_lines = [];


// load accounting rules
$products = loadXlsFile($path.'products.csv');

foreach (glob($path."*R_Packs.csv") as $file) {



    $path_parts = pathinfo($file);
    $filetype = IOFactory::identify($file);

    $filename = $path_parts['filename'];

    $nomenc_lines = loadXlsFile($path.$filename.'_Nomenc.csv');


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

            $centers_map = [
                'GG' => [
                    'prefix_list'           => ['AE','BA','BG','BO','BP','BR','CH','CO','DA','HA','HU','LA','LO','LP','LS','MA','MO','VE','WC','WE','GG', ''],
                ],
                'EU' => [
                    'prefix_list'           => ['EU', 'GA', ''],
                ],
                'HL' => [
                    'prefix_list'           => ['HL', 'GA', ''],
                ],
                'OV' => [
                    'prefix_list'           => ['OV', 'GA', ''],
                ],
                'LO' => [
                    'prefix_list'           => ['LO', 'GA', ''],
                ],
                'RO' => [
                    'prefix_list'           => ['RO', 'GA', ''],
                ],
                'VS' => [
                    'prefix_list'           => ['VS', 'GA', ''],
                ],
                'WA' => [
                    'prefix_list'           => ['WA', 'GA', ''],
                ]
            ];

            /*
                resolve center prefix
            */
            $center_prefix = '';

            if(strpos($filename, 'GiGr') !== false) {
                $center_prefix = 'GG';
            }
            else {
                if(strpos($filename, 'Louv') !== false) {
                    $center_prefix = 'LO';
                }
                else if(strpos($filename, 'Eupe') !== false) {
                    $center_prefix = 'EU';
                }
                else if(strpos($filename, 'HanL') !== false) {
                    $center_prefix = 'HL';
                }
                else if(strpos($filename, 'Ovif') !== false) {
                    $center_prefix = 'OV';
                }
                else if(strpos($filename, 'Roch') !== false) {
                    $center_prefix = 'RO';
                }
                else if(strpos($filename, 'Wann') !== false) {
                    $center_prefix = 'WA';
                }
                else if(strpos($filename, 'Vill') !== false) {
                    $center_prefix = 'VS';
                }
            }

            $prefixes = $centers_map[$center_prefix]['prefix_list'];

            // l'id du pack est arbitraire : on ne devrait pas l'utiliser
            $pack_id = $line['Numéro_Pack'];


            /*
            => retrouver le produit en fonction
                - du centre
                - de la tranche d'âge du produit correspondant au pack (sur base du Codif_Mnémo_Pack - pour le pack produit; et Codif_Mnémo_Nomenc pour le produit/ligne de pack)
             */
            // remove leading §§~_
             $pack_code = substr($line['Codif_Mnémo_Pack'], 6);

            // retrieve pack from the list of products

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

            if(!count($matches)) {
                die('no value found for pack '.$pack_code);
            }

            foreach($matches as $index) {
                // each product is a pack
                $product = $products[$index];
                list($product_category, $product_code, $product_option) = explode('-', $product['sku']);

            /*

            dans le fichier _R_Packs_nomenc correspondant, prendre toutes les lignes  avec Numéro_Pack correspondant

            utiliser Codif_Mnémo_Nomenc pour retrouver le product correspondant
            il doit y avoir un match au niveau du suffixe
            */

                echo PHP_EOL.PHP_EOL.$product['sku'].PHP_EOL;
                foreach($nomenc_lines as $nomenc_line) {
                    if($pack_id == $nomenc_line['Numéro_Pack']) {
                        $nomenc_code = substr($nomenc_line['Codif_Mnémo_Nomenc'], 5);
                        $nomenc_code = str_replace(['à', 'ï', 'î', 'é', 'ê','è', 'ë', 'û'], ['a', 'i', 'i', 'e', 'e', 'e', 'e', 'u'], $nomenc_code);
                        $subproduct_sku = $product_category.'-'.$nomenc_code.'-'.$product_option;
                        
                        $subproduct_id = 0;
                        foreach($products as $subproduct) {
                            if($subproduct['sku'] == $subproduct_sku) {
                                $subproduct_id = $subproduct['id'];
                                break;
                            }
                        }

                        if(!$subproduct_id) {
                            $subproduct_sku = $nomenc_code.'-'.$product_option;

                            foreach($products as $subproduct) {
                                if($subproduct['sku'] == $subproduct_sku) {
                                    $subproduct_id = $subproduct['id'];
                                    break;
                                }
                            }
                        }

                        if(!$subproduct_id) {
                            echo('no match for subproduct '.$subproduct_sku.PHP_EOL);
                        }


                    }
                }
                
            }



        }
    }
    break;

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