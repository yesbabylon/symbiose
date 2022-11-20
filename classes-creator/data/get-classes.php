<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;
use equal\data\DataAdapter;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of a document identified by given hash.',
    'params'        => [
        'path' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'string'
        ],
    ],
    'access' => [
        'visibility'        => 'public'
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];

$arr = [
    "types" => ['boolean', 'integer', 'float', 'string', 'time', 'date', 'datetime', 'array', 'many2one', 'one2many', 'many2many', 'html', 'binary'],
    "packages" => []
];

// function getsubpackage($filename, $filename_two){
//     foreach(glob($filename."/classes/*", GLOB_ONLYDIR) as $subPackage){
//         $subPackager = basename($subPackage);
//         $arr["packages"][$filename_two][] = $subPackager;

//         if(glob($subPackage, GLOB_ONLYDIR) !== null){
//             // getsubpackage()
//         }
//     };
// }

$stack =  [];

foreach (glob("packages/*/manifest.json") as $filename) {
    // $filename = trim($filename, "manifest.json");
    // $filename = trim($filename, "packages");

    $filename = str_replace('/manifest.json', '', $filename);
    $filename_two = str_replace('packages/', '', $filename);
    $arr['packages'][$filename_two]= [];

    $stack[]= $filename."/classes/*";

    while((count($stack))>0){
        foreach(glob($stack[0]) as $classe){
            if(strpos($classe, 'class.php')){
                $arr["packages"][$filename_two][] = $classe;
            }else{
                $stack[] = $classe;
                // build tree
            }
        }
        array_shift($stack);
    }


    // first option


    // no subfolder
    // if(glob($filename."/classes/*", GLOB_ONLYDIR) == null){
    //     foreach(glob($filename."/classes/*") as $classe){

    //         $classe = basename($classe);
    //         $arr["packages"][$filename_two][] = $classe;
    //     }
    // }


    // foreach(glob($filename."/classes/*", GLOB_ONLYDIR) as $subPackage){
    //     $subPackager = basename($subPackage);

    //     foreach(glob($subPackage.'/*') as $classe){

    //         $classe = basename($classe);
    //         $arr["packages"][$filename_two][$subPackager][] = $classe;
    //     }

    // }
}

// getting the types from the DataAdapter
// $dataAdapter = new DataAdapter();
// $types = $dataAdapter->config;





$context->httpResponse()
        ->body($arr)
        ->send();