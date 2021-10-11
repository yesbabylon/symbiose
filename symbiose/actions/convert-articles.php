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

// charger tous les fichiers R_Articles.csv

$path = "packages/symbiose/test/";


$Kaleo_commons = explode(PHP_EOL, file_get_contents($path.'Kaleo_commons.csv'));
$GG_commons = explode(PHP_EOL, file_get_contents($path.'GG_commons.csv'));
$GA_commons = explode(PHP_EOL, file_get_contents($path.'GA_commons.csv'));


$pricelists = [];
$prices = [];

$product_models = [];
$products = [];

$product_attributes = [];

$products_map = [];

// remember allocated SKUs
$sku_cache = [];


$pack_lines = [];

if(file_exists($path.'pack_lines.csv')) {
    $pack_lines = loadXlsFile($path.'pack_lines.csv');
}

// load accounting rules
$accounting_rules = loadXlsFile($path.'lodging_finance_accounting_AccountingRule.xls');


foreach (glob($path."*R_Articles.csv") as $file) {

    $path_parts = pathinfo($file);
    $filetype = IOFactory::identify($file);

    $filename = $path_parts['filename'];


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

            $mnemo = $line['Codif_Mnémo'];
            $label = trim($line['Libellé_Article'], '_');

            $date_from_array = date_parse($line['DateApp_Début']);
            $date_to_array = date_parse($line['DateApp_Fin']);

            $centers_map = [
                'GG' => [
                    'pricelist_category_id' => 1,
                    'groups_ids'            => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21],
                    'center_category_id'    => 2
                ],
                'EU' => [
                    'pricelist_category_id' => 2,
                    'groups_ids'            => [22],
                    'center_category_id'    => 1
                ],
                'HL' => [
                    'pricelist_category_id' => 3,
                    'groups_ids'            => [24],
                    'center_category_id'    => 1
                ],
                'OV' => [
                    'pricelist_category_id' => 4,
                    'groups_ids'            => [25],
                    'center_category_id'    => 1
                ],
                'LO' => [
                    'pricelist_category_id' => 5,
                    'groups_ids'            => [26],
                    'center_category_id'    => 1
                ],
                'RO' => [
                    'pricelist_category_id' => 6,
                    'groups_ids'            => [27],
                    'center_category_id'    => 1
                ],
                'VS' => [
                    'pricelist_category_id' => 7,
                    'groups_ids'            => [23],
                    'center_category_id'    => 3
                ],
                'WA' => [
                    'pricelist_category_id' => 8,
                    'groups_ids'            => [28],
                    'center_category_id'    => 1
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

            $pricelist_category_id = $centers_map[$center_prefix]['pricelist_category_id'];
            $center_category_id = $centers_map[$center_prefix]['center_category_id'];

            $sku_prefix = '';

            /*
                resolve family_id
            */
            if(in_array($mnemo, $Kaleo_commons)) {
                $family_id = 1;
            }

            // products have always a prefix
            $sku_prefix = $center_prefix;

            switch($center_prefix) {
                case 'GG':
                    $family_id = 3;
                    break;
                case 'VS':
                    $family_id = 4;
                    break;
                case 'LO':
                    $family_id = 5;
                    $sku_prefix = 'GA';
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
                case 'EU':
                    $family_id = 6;
                    $sku_prefix = 'GA';
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
                case 'HL':
                    $family_id = 7;
                    $sku_prefix = 'GA';
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
                case 'OV':
                    $family_id = 8;
                    $sku_prefix = 'GA';                    
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
                case 'RO':
                    $family_id = 9;
                    $sku_prefix = 'GA';
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
                case 'WA':
                    $family_id = 10;
                    $sku_prefix = 'GA';                    
                    if(in_array($mnemo, $GA_commons)) {
                        $family_id = 2;
                    }
                    break;
            }



            /*
                resolve groups_ids
            */
            $groups_ids = $centers_map[$center_prefix]['groups_ids'];

            if($center_prefix == 'GG') {
                if(stripos($label, 'Arbrefontaine') !== false) {
                    $groups_ids = [2];
                    $sku_prefix = 'AP';
                    if(stripos($label, 'ecole') !== false || stripos($label, 'école') !== false) {
                        $groups_ids = [1];
                        $sku_prefix = 'AE';
                    }
                }
                else if(stripos($label, 'Basseilles') !== false) {
                    $groups_ids = [3];
                    $sku_prefix = 'BA';
                }
                else if(stripos($label, 'Bastogne') !== false) {
                    $groups_ids = [4];
                    $sku_prefix = 'BG';
                }
                else if(stripos($label, 'Bohan') !== false) {
                    $groups_ids = [5];
                    $sku_prefix = 'BO';
                }
                else if(stripos($label, 'Bruly') !== false || stripos($label, 'Brûly') !== false) {
                    $groups_ids = [6];
                    $sku_prefix = 'BP';
                    if(stripos($label, '- Ecole') !== false || stripos($label, '- E') !== false) {
                        $groups_ids = [7];
                        $sku_prefix = 'BR';
                    }
                }
                else if(stripos($label, 'Chassepierre') !== false) {
                    $groups_ids = [8];
                    $sku_prefix = 'CH';
                }
                else if(stripos($label, 'Cornimont') !== false) {
                    $groups_ids = [9];
                    $sku_prefix = 'CO';
                }
                else if(stripos($label, 'Daverdisse') !== false) {
                    $groups_ids = [10];
                    $sku_prefix = 'DA';
                }
                else if(stripos($label, 'Hastière') !== false) {
                    $groups_ids = [11];
                    $sku_prefix = 'HA';
                }
                else if(stripos($label, 'Haus-') !== false) {
                    $groups_ids = [12];
                    $sku_prefix = 'HU';
                }
                else if(stripos($label, 'Reid') !== false) {
                    $groups_ids = [13];
                    $sku_prefix = 'LA';
                }
                else if(stripos($label, 'Latour') !== false) {
                    $groups_ids = [14];
                    $sku_prefix = 'LO';
                }
                else if(stripos($label, 'Louette') !== false) {
                    $groups_ids = [15];
                    $sku_prefix = 'LP';
                }
                else if(stripos($label, 'Lesse') !== false) {
                    $groups_ids = [16];
                    $sku_prefix = 'LS';
                }
                else if(stripos($label, 'Maboge') !== false) {
                    $groups_ids = [17];
                    $sku_prefix = 'MA';
                }
                else if(stripos($label, 'Mormont') !== false) {
                    $groups_ids = [18];
                    $sku_prefix = 'MO';
                }
                else if(stripos($label, 'Vergnies') !== false) {
                    $groups_ids = [19];
                    $sku_prefix = 'VE';
                }
                else if(stripos($label, 'Wauthier') !== false) {
                    $groups_ids = [20];
                    $sku_prefix = 'WC';
                }
                else if(stripos($label, 'Werbomont') !== false) {
                    $groups_ids = [21];
                    $sku_prefix = 'WE';
                }
            }

            /*
                resolve price_list name
            */
            $year_from = (int) $date_from_array['year'];
            $year_to = (int) $date_to_array['year'];
            $month_to = (int) $date_to_array['month'];
            $day_to = (int) $date_to_array['day'];

            if($year_to == 2100) {
                $year_from = 2000;
                $year_to = 2099;
            }
            else {
                if($year_from != $year_to) {

                    if($day_to == 1 && $month_to == 1) {
                        $year_to = $year_to - 1;
                        $month_to = 12;
                        $day_to = 31;
                    }
                    else {
                        $year_from = $year_to - 1;
                    }
                    
                }
            }
            $pricelist_name = $center_prefix.' '.$year_from;

            if($year_to != $year_from) {
                $pricelist_name .= '-'.$year_to;
            }

            $pricelist_id = 0;
            foreach($pricelists as $index => $pricelist) {
                if($pricelist['name'] == $pricelist_name) {
                    $pricelist_id = $pricelist['id'];
                    break;
                }
            }

            if(!$pricelist_id) {
                $pricelist_id = count($pricelists) + 1;
                $pricelists[] = [
                    'id'                     => $pricelist_id,
                    'name'                   => $pricelist_name,
                    'date_from'              => sprintf("%4d-%02d-%02d", $year_from, $date_from_array['month'], $date_from_array['day']),
                    'date_to'                => sprintf("%4d-%02d-%02d", $year_to, $month_to, $day_to),
                    'price_list_category_id' => $pricelist_category_id
                ];
            }



            /*
                Resolve category_id
            */
            $category_id = 0;

            if(strpos($mnemo, '_CDV') !== false) {
                $category_id = 1;
            }

            /*
                Resolve stat_section_id
            */
            $stat_map = [
                'ANIM'  => 1,
                'BAR'   => 2,
                'BOU'   => 3,
                'CAMP'  => 4,
                'DIV'   => 5,
                'FRAI'  => 6,
                'GITE'  => 7,
                'LOCS'  => 8,
                'RST'   => 9,
                'SEJ'   => 10,
                'VOY'   => 11,
                'STAG'  => 12
            ];

            $stat_section_id = $stat_map[$line['StatPr']];


            /*
                resolve SKU suffix
            */
            $sku_map = [
                'moins de 3 ans'        => '0_3',
                'de 3 ans à 5 ans'      => '3_6',
                'de 3 ans à 11 ans'     => '3_12',
                'de 6 ans à 11 ans'     => '6_12',
                'de 12 ans à 25 ans'    => '12_26',
                'moins de 26 ans'       => 'M26',
                'plus de 26 ans'        => '26_99'
            ];

            $sku_suffix = '';

            foreach($sku_map as $key => $suffix) {
                if(stripos($label, $key) !== false) {
                    $sku_suffix = $suffix;
                    break;
                }
            }
            if($sku_suffix == '') {
                $sku_suffix = 'A';
            }

            /*
                resolve accounting rule
            */
            $accounting_rule_id = 0;
            foreach($accounting_rules as $accounting_rule) {
                if($accounting_rule['code_legacy'] == $line['ReglCpta'] && $accounting_rule['center_category_id'] == $center_category_id) {
                    $accounting_rule_id = $accounting_rule['id'];
                    break;
                }
            }


            // check if a model by that name does already exist
            $product_model_id = 0;
            
            foreach($product_models as $product_model_index => $product_model) {
                if( strcasecmp($product_model['name'], $line['Libellé_Mnémo']) == 0 ) {
                    $product_model_id = $product_model['id'];
                    break;
                }
            }

            $can_sell = false;

            if ((int) $date_to_array['year'] == 2000 || (int) $date_to_array['year'] >= (int) date('Y')) {
                $can_sell = true;
            }

            $product_sku = trim($sku_prefix.'-'.trim($line['Codif_MnémCourt'], '_').'-'.$sku_suffix, '-');
            $product_sku = str_replace(['à', 'ï', 'î', 'é', 'ê','è', 'ë', 'û'], ['a', 'i', 'i', 'e', 'e', 'e', 'e', 'u'], $product_sku);

// #reminder - this requires to perform convertion in 2-pass (we need the products for the packs and this requires the packs)

            foreach($pack_lines as $pack_line) {
                if($product_sku == $pack_line['parent_sku'] || $product_sku == $pack_line['child_sku']) {
                    $can_sell = true;
                    break;
                }
            }

            if(!$product_model_id) {
                $product_model_id = count($product_models) + 1;

                $product_model = [
                    'id'                    => $product_model_id,
                    'name'                  => $line['Libellé_Mnémo'],
                    'family_id'             => $family_id,
                    'groups_ids'            => '['.implode(',', $groups_ids).']',
                    'categories_ids'        => '['.$category_id.']',
                    'is_pack'               => 0,
                    'can_sell'              => $can_sell,
                    'stat_section_id'       => $stat_section_id,
                    'is_meal'               => (int) in_array((int) $line['Code_Pension'], [5,6,7,8,9]),
                    'is_accomodation'       => (int) ((int) $line['Code_Pension'] == 4),
                    'qty_accounting_method' => ((int) $line['Code_Pension'] == 4)?'accomodation':'person',
                    'has_duration'          => 0,
                    'duration'              => 0,
                    'capacity'              => 0
                ];


                /*
                    check is_pack
                */
                $is_pack = (strpos($mnemo, '§§~_') !== false);
                if($is_pack) {
                    $product_model['is_pack'] = true;
                    $product_model['qty_accounting_method'] = 'unit';
                }

                /*
                    check capacity
                */
                preg_match('/([1-9]{1}) pers/', $label, $matches, PREG_OFFSET_CAPTURE);

                if(count($matches)) {
                    $product_model['is_accomodation'] = true;
                    $product_model['qty_accounting_method'] = 'accomodation';
                    $product_model['capacity'] = $matches[1][0];
                }
                else {
                    $product_model['qty_accounting_method'] = 'person';
                }

                /*
                    check duration
                */
                preg_match('/([1-9]{1}) ?nui/', $label, $matches, PREG_OFFSET_CAPTURE);
                if(count($matches)) {
                    $product_model['has_duration'] = true;
                    $product_model['duration'] = $matches[1][0];;
                }
                else {
                    preg_match('/([1-9]{1}) ?jour/', $label, $matches, PREG_OFFSET_CAPTURE);
                    if(count($matches)) {
                        $product_model['has_duration'] = true;
                        $product_model['duration'] = $matches[1][0];;
                    }
                    else if($line['Codif_Mnémo'] == '3J') {
                        $product_model['has_duration'] = true;
                        $product_model['duration'] = 3;
                    }
                    else if($line['Codif_Mnémo'] == '4J') {
                        $product_model['has_duration'] = true;
                        $product_model['duration'] = 4;
                    }
                    else if($line['Codif_Mnémo'] == '5J') {
                        $product_model['has_duration'] = true;
                        $product_model['duration'] = 5;
                    }
                }

                $product_models[] = $product_model;
            }
            else {
                // if product already exists : append groups_ids
                $p_groups_ids = json_decode($product_model['groups_ids']);
                $p_groups_ids = array_unique(array_merge($p_groups_ids, $groups_ids));
                $product_models[$product_model_index]['groups_ids'] = '['.implode(',', $p_groups_ids).']';
                $product_models[$product_model_index]['can_sell'] = $product_models[$product_model_index]['can_sell'] || $can_sell;
            }            

            

            $product_id = 0;

            if(isset($sku_cache[$product_sku])) {
                $product_id = $sku_cache[$product_sku];
            }

            $product_arributes_map = [
                'A'     =>1,
                '0_3'   =>2,
                '3_6'   =>3,
                '3_12'  =>4,
                '6_12'  =>5,
                '12_26' =>6,
                '26_99' =>7,
                'M12'   =>8,
                'M26'   =>9
            ];

            if(!$product_id) {
                // $product_id = sprintf("%d%04d", $pricelist_category_id, $line['Code_Article']);
                $product_id = count($products) + 1;

                $products[] = [
                    'id'                => $product_id,
                    'sku'               => $product_sku,
                    'name'              => $label,
                    'product_model_id'  => $product_model_id,
                    'stat_section_id'   => $stat_section_id,
                    'is_pack'           => $is_pack
                ];

                $sku_cache[$product_sku] = $product_id;
                $product_attributes[] = [
                    'id'                => count($product_attributes) + 1,
                    'product_id'        => $product_id,
                    'option_id'         => 1,
                    'option_value_id'   => $product_arributes_map[$sku_suffix]
                ];

                if($sku_suffix == '26_99') {
                    $sku_all = str_replace('26_99', 'A', $product_sku);
                    if(!isset($sku_cache[$sku_all])) {

                        $product_id = count($products) + 1;

                        $products[] = [
                            'id'                => $product_id,
                            'sku'               => $sku_all,
                            'name'              => str_replace('Plus de 26 ans', 'Tous les âges', $label),
                            'product_model_id'  => $product_model_id,
                            'stat_section_id'   => $stat_section_id,
                            'is_pack'           => $is_pack
                        ];

                        $sku_cache[$sku_all] = $product_id;

                        $product_attributes[] = [
                            'id'                => count($product_attributes) + 1,
                            'product_id'        => $product_id,
                            'option_id'         => 1,
                            'option_value_id'   => $product_arributes_map[$sku_suffix]
                        ];

                    }
                }
            }


            $products_map[] = [
                'legacy_code' => $center_prefix.'-'.$line['Code_Article'],
                'sku' => $product_sku
            ];


            /*
                create price
            */
            $prices[] = [
                'id'                    => count($prices) + 1,
                'product_id'            => $product_id,
                'type'                  => 'direct',
                'price'                 => floatval($line['Tarif_Unitaire']),
                'price_list_id'         => $pricelist_id,
                'accounting_rule_id'    => $accounting_rule_id
            ];

        }
    }

}



if(count($pricelists)) {
    $data = [];
    $first = $pricelists[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($pricelists as $pricelist) {
        $data[] = implode(';', array_values($pricelist));
    }
    file_put_contents($path."price_lists.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}


if(count($prices)) {
    $data = [];
    $first = $prices[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($prices as $price) {
        $data[] = implode(';', array_values($price));
    }
    file_put_contents($path."prices.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}

if(count($product_models)) {
    $data = [];
    $first = $product_models[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($product_models as $product_model) {
        $data[] = implode(';', array_values($product_model));
    }
    file_put_contents($path."product_models.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}

if(count($products)) {
    $data = [];
    $first = $products[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($products as $product) {
        $data[] = implode(';', array_values($product));
    }
    file_put_contents($path."products.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}

if(count($product_attributes)) {
    $data = [];
    $first = $product_attributes[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($product_attributes as $product_attribute) {
        $data[] = implode(';', array_values($product_attribute));
    }
    file_put_contents($path."product_attributes.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
}


if(count($products_map)) {
    $data = [];
    $first = $products_map[0];
    $header = array_keys($first);
    $data[] = implode(';', $header);

    foreach($products_map as $product_map) {
        $data[] = implode(';', array_values($product_map));
    }
    file_put_contents($path."products_map.csv", "\xEF\xBB\xBF".implode(PHP_EOL, $data));    
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