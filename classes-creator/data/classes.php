<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;
use equal\data\DataAdapter;

list($params, $providers) = announce([
    'description'   => 'Return the packages paths.',
    'params'        => [
        'package' =>  [
            'description'   => 'Name of package.',
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
    "packages" => []
];

// function getsubpackage($filename, $package_name){
//     foreach(glob($filename."/classes/*", GLOB_ONLYDIR) as $subPackage){
//         $subPackager = basename($subPackage);
//         $arr["packages"][$filename_two][] = $subPackager;

//         if(glob($subPackage, GLOB_ONLYDIR) !== null){
//             // getsubpackage()
//         }
//     };
// }

$stack =  [];

foreach (glob("packages/*", GLOB_ONLYDIR) as $filename) {
    // $filename = trim($filename, "manifest.json");
    // $filename = trim($filename, "packages");

    $package_name = str_replace('packages/', '', $filename);
    $arr['packages'][$package_name] = [];

    $stack[] = $filename."/classes";



    while(count($stack) > 0){
        $item = array_shift($stack);
        $files = glob($item.'/*') ;

        foreach($files as $file){

            if(is_file($file) && strpos($file, '.class.php') !== false) {
                $file = str_replace(['packages/', 'classes/', '.class.php'], '', $file);
                $arr["packages"][$package_name][] = str_replace('/', '\\', $file);
            }
            elseif(is_dir($file)) {
                $stack[] = $file;
            }
            // $parts = explode('/', str_replace('classes/', '', $class));
        }
    }


    // // no subfolder
    // if(glob($filename."/classes/*", GLOB_ONLYDIR) == null){
    //     foreach(glob($filename."/classes/*") as $classe){

    //         $classe = basename($classe);
    //         $arr["packages"][$package_name][] = $classe;
    //     }
    // }


    // foreach(glob($filename."/classes/*", GLOB_ONLYDIR) as $subPackage){
    //     $subPackager = basename($subPackage);

    //     foreach(glob($subPackage.'/*') as $classe){

    //         $classe = basename($classe);
    //         $arr["packages"][$package_name][$subPackager][] = $classe;
    //     }

    // }
}

// getting the type from the DataAdapter
// $dataAdapter = new DataAdapter();
// $type = $dataAdapter->config;





$context->httpResponse()
        ->body($arr)
        ->send();