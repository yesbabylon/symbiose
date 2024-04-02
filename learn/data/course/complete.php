<?php
use learn\UserAccess;

list($params, $providers) = announce([
    'description'   => "Checks if a pack has been fully completed by current user.",
    'params'        => [
        'course_id' =>  [
            'description'   => 'Course identifier (id field).',
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

if(!isset($_COOKIE) || !isset($_COOKIE["wp_lms_user"]) || !is_numeric($_COOKIE["wp_lms_user"])) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$user_id = (int) $_COOKIE["wp_lms_user"];

if($user_id <= 0) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}


/*
    Check if user is granted access over given pack
*/

// root(1), admin(2) and main author(3) are always granted
if($user_id > 3) {
    // check that the user is granted to access target module
    $access = UserAccess::search([ ['course_id', '=', $params['course_id']], ['user_id', '=', $user_id] ])->ids();
    if(!$access || !count($access)) {
        throw new Exception('missing_licence', QN_ERROR_NOT_ALLOWED);
    }
}


$access = UserAccess::search([ ['course_id', '=', $params['course_id']], ['user_id', '=', $user_id] ])->read(['is_complete', 'code', 'code_alpha'])->first();

$result = ['complete' => true];

if(!$access) {
    throw new Exception('unknown_program', QN_ERROR_UNKNOWN_OBJECT);
}

$result['complete'] = (bool) $access['is_complete'];

$context->httpResponse()
        ->body($result)
        ->send();