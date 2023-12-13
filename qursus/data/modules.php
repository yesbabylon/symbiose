<?php
use qursus\UserAccess;
use qursus\UserStatus;


list($params, $providers) = announce([
    'description'   => "Returns a list of all modules for a given pack, enriched with current user status.",
    'params'        => [
        'pack_id' =>  [
            'description'   => 'Pack identifier (id field).',
            'type'          => 'integer',
            'required'      => true
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm) = [ $providers['context'], $providers['orm'] ];

/*
    Retrieve current user id
*/
/*
if(!isset($_COOKIE) || !isset($_COOKIE["wp_lms_user"]) || !is_numeric($_COOKIE["wp_lms_user"])) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$user_id = (int) $_COOKIE["wp_lms_user"];

if($user_id <= 0) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}
*/

/*
    Check if user is granted access
*/

// check that the user is granted to access target module
/*
$access = UserAccess::search([ ['pack_id', '=', $params['pack_id']], ['user_id', '=', $user_id] ])->ids();
if(!$access || !count($access)) {
    throw new Exception('missing_licence', QN_ERROR_NOT_ALLOWED);
}
*/

/*
    Collect modules from requested pack
*/

$json = run('get', 'model_collect', [
            'entity' 	=> 'qursus\Module',
            'domain' 	=> ['pack_id', '=', $params['pack_id'] ],
            'fields' 	=> ['identifier', 'title', 'duration', 'description', 'page_count'],
            'order' 	=> 'identifier',
            'sort' 		=> 'asc'
        ]);

$data = json_decode($json, true);
if(isset($data['errors'])) {
    foreach($data['errors'] as $name => $message) throw new Exception($message, qn_error_code($name));
}

$modules = $data;

/*
    Enrich modules with current user statuses
*/

foreach($modules as $index => $module) {
    $percent = 0;
    $status = UserStatus::search([ ['module_id', '=', $module['id']], ['user_id', '=', $user_id] ])->read(['page_count', 'chapter_index', 'page_index', 'is_complete'])->first();
    if($status && count($status)) {
        $percent = intval( (($status['page_count']+1) / $module['page_count']) *100 );
        $modules[$index]['status'] = 'in progress';
        if($status['is_complete']) {
            $percent = 100;
            $modules[$index]['status'] = 'completed';
        }
    }
    else {
        $modules[$index]['status'] = 'not started';
    }
    $modules[$index]['percent'] = $percent;
}

$context->httpResponse()
->body($modules)
->send();