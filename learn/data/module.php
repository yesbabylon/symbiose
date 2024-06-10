<?php

use learn\UserAccess;
use learn\Module;

list($params, $providers) = announce([
    'description' => "Returns a fully loaded JSON formatted single module.",
    'params' => [
        'id' => [
            'description' => 'Module identifier (id field).',
            'type' => 'integer',
            'required' => true
        ],
        'lang' => [
            'description' => 'Language requested for multilang values.',
            'type' => 'string',
            'default' => constant('DEFAULT_LANG')
        ]
    ],
    'response' => [
        'content-type' => 'application/json',
        'charset' => 'utf-8',
        'accept-origin' => '*',
        // 'cacheable'     => true,
        'expires' => 3600
    ],
    'providers' => ['context', 'orm', 'auth']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

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

$search = Module::search(['id', '=', $params['id']]);


/*
    Check if user is granted access
*/

// root(1), admin(2) and main author(3) are always granted
/*
if($user_id > 3) {
    // retrieve course_id
    $collection = $search->read(['course_id'])->get(true);
    if(!$collection || !count($collection)) {
        throw new Exception('unknown_module', QN_ERROR_INVALID_PARAM);
    }
    $instance = $collection[0];
    if(!$instance || !isset($instance['course_id'])) {
        throw new Exception('unknown_error', QN_ERROR_UNKNOWN);
    }
    $course_id = $instance['course_id'];

    // check that the user is granted to access target module
    $access = UserAccess::search([ ['course_id', '=', $course_id], ['user_id', '=', $user_id] ])->ids();
    if(!$access || !count($access)) {
        throw new Exception('missing_licence', QN_ERROR_NOT_ALLOWED);
    }
}
*/

$module = $search->read([
    'id',
    'name',
    'identifier',
    'order',
    'title',
    'description',
    'duration',
    'page_count',
    'chapter_count',
    'course_id' => ['id', 'name', 'title', 'subtitle', 'description', 'langs_ids' => ['id', 'name', 'code']],
    'chapters' => [
        'id',
        'identifier',
        'order',
        'title',
        'duration',
        'description',
        'page_count',
        'pages' => [
            'id',
            'identifier',
            'order',
            'next_active',
            'next_active_rule',
            'leaves' => [
                'id',
                'identifier',
                'order',
                'visible',
                'visibility_rule',
                'background_image',
                'background_stretch',
                'background_opacity',
                'contrast',
                'groups' => [
                    'id',
                    'identifier',
                    'order',
                    'direction',
                    'row_span',
                    'visible',
                    'visibility_rule',
                    'fixed',
                    'widgets' => [
                        'id',
                        'identifier',
                        'order',
                        'content',
                        'type',
                        'section_id',
                        'image_url',
                        'video_url',
                        'sound_url',
                        'has_separator_left',
                        'has_separator_right',
                        'align',
                        'on_click'
                    ]
                ]
            ],
            'sections' => [
                'id',
                'identifier',
                'order',
                'pages' => [
                    'id',
                    'identifier',
                    'order',
                    'next_active',
                    'leaves' => [
                        'id',
                        'identifier',
                        'order',
                        'visible',
                        'background_image',
                        'background_stretch',
                        'background_opacity',
                        'contrast',
                        'groups' => [
                            'id',
                            'identifier',
                            'order',
                            'direction',
                            'row_span',
                            'visible',
                            'fixed',
                            'widgets' => [
                                'id',
                                'identifier',
                                'order',
                                'content',
                                'type',
                                'section_id',
                                'image_url',
                                'video_url',
                                'sound_url',
                                'has_separator_left',
                                'has_separator_right',
                                'align',
                                'on_click'
                            ]
                        ]
                    ]
                ]
            ]
        ]

    ]
],
    $params['lang']
)
    ->adapt('json')
    ->first(true);

if (!$module) {
    throw new Exception("unknown_entity", QN_ERROR_INVALID_PARAM);
}

$context->httpResponse()
    ->body($module)
    ->send();
