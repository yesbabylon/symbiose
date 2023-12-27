<?php
use qursus\UserAccess;
use qursus\Bundle;

list($params, $providers) = announce([
    'description'   => "Sends either a single attachment or a zip archive containing all bundle's attachments.",
    'params'        => [
        'id' =>  [
            'description'   => 'Unique bundle identifier (id field).',
            'type'          => 'integer',
            'required'      => true
        ],
        'lang' =>  [
            'description'   => 'Language requested for multilang values.',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*',
        'cacheable'     => false,
        'expires'       => 3600
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm) = [ $providers['context'], $providers['orm'] ];

/*
    Retrieve current user id
*/
if(!isset($_COOKIE) || !isset($_COOKIE["wp_lms_user"]) || !is_numeric($_COOKIE["wp_lms_user"])) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$user_id = (int) $_COOKIE["wp_lms_user"];

if($user_id <= 0) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$search = Bundle::search(['id', '=', $params['id']]);


/*
    Check if user is granted access
*/

// root(1), admin(2) and main author(3) are always granted
if($user_id > 3) {
    // retrieve pack_id
    $collection = $search->read(['pack_id'])->get(true);
    if(!$collection || !count($collection)) {
        throw new Exception('unknown_module', QN_ERROR_INVALID_PARAM);
    }
    $instance = $collection[0];
    if(!$instance || !isset($instance['pack_id'])) {
        throw new Exception('unknown_error', QN_ERROR_UNKNOWN);
    }
    $pack_id = $instance['pack_id'];

    // check that the user is granted to access target module
    $access = UserAccess::search([ ['pack_id', '=', $pack_id], ['user_id', '=', $user_id] ])->ids();
    if(!$access || !count($access)) {
        throw new Exception('missing_licence', QN_ERROR_NOT_ALLOWED);
    }
}

$bundle = $search->read([
        'id',
        'attachments_ids' => ['id', 'name', 'url']
    ], 
    $params['lang']
)
->first(true);

if(!$bundle) {
    throw new Exception("unknown_entity", QN_ERROR_INVALID_PARAM);
}


if(count($bundle['attachments_ids']) < 2) {
    if(count($bundle['attachments_ids']) < 1) {
        throw new Exception("missing_entity", QN_ERROR_UNKNOWN_OBJECT);
    }
    $attachment = array_pop($bundle['attachments_ids']);
    $data = file_get_contents($attachment['url']);
    $finfo = new finfo(FILEINFO_MIME);
    $content_type = ( explode(';', $finfo->buffer($data)) )[0];

    header('Content-Type: '.$content_type);
    header('Content-Length: ' . strlen($data));
    header('Content-Disposition: attachment; filename="'.basename($attachment['url']).'"');
}
// bundle all attachments to a zip archive
else {
    $tmpfile = tempnam(sys_get_temp_dir(), "zip");
    $zip = new ZipArchive();
    $zip->open($tmpfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach($bundle['attachments_ids'] as $attachment) {
        $data = file_get_contents($attachment['url']);        
        $zip->addFromString(basename($attachment['url']), $data);        
    }
    $zip->close();

    $data = file_get_contents($tmpfile);
    unlink($tmpfile); 

    header('Content-Type: application/zip');
    header('Content-Length: ' . strlen($data));
    header('Content-Disposition: attachment; filename="'.$bundle['name'].'.zip"');
}

print($data);
exit();


$context->httpResponse()
        ->body($bundle)
        ->send();