<?php

use learn\UserStatus;
use learn\UserAccess;
use learn\Module;
use learn\Chapter;

// AlexisVS: Todo: Test this code

list($params, $providers) = announce([
    'description' => "Handle action from user when performing a click to see next page of a given module.",
    'params' => [
        'module_id' => [
            'description' => 'Module unique identifier (id field).',
            'type' => 'integer',
            'required' => true
        ],
        'chapter_index' => [
            'description' => 'Chapter index within the module (0 to n).',
            'type' => 'integer',
            'required' => true
        ],
        'page_index' => [
            'description' => 'Page index within the chapter (0 to m).',
            'type' => 'integer',
            'required' => true
        ]
    ],
    'access' => 'public',
    'response' => [
        'content-type' => 'application/json',
        'charset' => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

/*
    Retrieve current user id
*/

// Todo: AlexisVS: This code below check if user is logged in for WordPress !!!!
//if(!isset($_COOKIE) || !isset($_COOKIE["wp_lms_user"]) || !is_numeric($_COOKIE["wp_lms_user"])) {
//    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
//}
//
//$user_id = (int) $_COOKIE["wp_lms_user"];

$user_id = $auth->userId();

if ($user_id <= 0) {
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$module = Module::id($params['module_id'])->read(['course_id', 'chapter_count', 'chapters_ids'])->first();

if (!$module) {
    throw new Exception('unknown_module', QN_ERROR_INVALID_PARAM);
}

$chapters_ids = $module['chapters_ids'];

if (count($chapters_ids) <= $params['chapter_index']) {
    throw new Exception('unknown_chapter', QN_ERROR_INVALID_PARAM);
}

$chapter = Chapter::id($chapters_ids[$params['chapter_index']])->read(['page_count'])->first();

if (!$chapter) {
    throw new Exception('unknown_chapter', QN_ERROR_INVALID_PARAM);
}


$status_ids = UserStatus::search([['user_id', '=', $user_id], ['module_id', '=', $params['module_id']]])->ids();

if (count($status_ids) < 1) {
    // first status recording for user_id: module_id
    UserStatus::create([
        'user_id' => $user_id,
        'module_id' => $params['module_id'],
        'course_id' => $module['course_id'],
        'chapter_index' => $params['chapter_index'],
        'page_index' => $params['page_index']
    ]);
} else {
    $status = UserStatus::ids($status_ids)->read(['course_id', 'chapter_index', 'page_index', 'page_count', 'is_complete'])->first();

    // we expect and allow one page turn at a time
    if (($params['chapter_index'] == $status['chapter_index'] && $params['page_index'] == $status['page_index'] + 1)
        || ($params['chapter_index'] == $status['chapter_index'] + 1 && $params['page_index'] == 0)) {
        UserStatus::ids($status_ids)->update([
            'chapter_index' => $params['chapter_index'],
            'page_index' => $params['page_index'],
            'page_count' => $status['page_count'] + 1
        ]);
    }

    // on last page or exceeded pages, set pack as complete
    if ($params['chapter_index'] + 1 >= $module['chapter_count'] && $params['page_index'] + 1 >= $chapter['page_count']) {
        // is it the first time we detect completeness?
        if (!$status['is_complete']) {
            // mark the program as complete for current user
            UserStatus::ids($status_ids)->update(['is_complete' => true]);
            $access = UserAccess::search([['course_id', '=', $status['course_id']], ['user_id', '=', $user_id]])->read(['is_complete'])->first();
            if ($access && $access['is_complete']) {
                // Send email offers the user to participate in an anonymous survey
                run('do', 'learn_survey', [
                    'course_id' => $status['course_id'],
                    'user_id' => $user_id
                ]);
            }
        }
    }
}

$context->httpResponse()
    ->status(204)
    ->send();