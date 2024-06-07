<?php

use learn\Course;
use learn\UserAccess;


list($params, $providers) = announce([
    'description' => "Returns the given course with all of its nested entities.",
    'deprecated' => true,
    'params' => [
        'course_id' => [
            'description' => 'Course identifier (id field).',
            'type' => 'integer',
            'required' => true
        ],
    ],
    'response' => [
        'content-type' => 'application/json',
        'charset' => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers' => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();

$access_ids = UserAccess::search([['course_id', '=', $params['course_id']], ['user_id', '=', $user_id]])->ids();

// Todo: AlexisVS: Disabled for development purpose
//if (!count($access_ids)) {
//    throw new Exception('missing_licence', QN_ERROR_NOT_ALLOWED);
//}

$data = Course::search(['id', '=', $params['course_id']])->read([
    'name',
    'title',
    'subtitle',
    'description',
    'langs_ids' => ['id', 'name', 'code'],
    'modules' => [
        'id',
        'identifier',
        'order',
        'title',
        'description',
        'duration',
        'page_count',
        'chapter_count',
        'chapters' => [
            'id',
            'identifier',
            'order',
            'title',
            'duration',
            'description',
            'page_count',
//            'pages' => [
//                'id',
//                'identifier',
//                'order',
////                'next_active',
////                'next_active_rule',
//                // ! Cut here
//                'leaves' => [
//                    'id',
//                    'identifier',
//                    'order',
////                    'visible',
////                    'visibility_rule',
//                    'background_image',
//                    'background_stretch',
//                    'background_opacity',
//                    'contrast',
//                    'groups' => [
//                        'id',
////                        'identifier',
//                        'order',
//                        'direction',
//                        'row_span',
////                        'visible',
////                        'visibility_rule',
//                        'fixed',
//                        'widgets' => [
//                            'id',
//                            'identifier',
//                            'order',
//                            'content',
////                            'type',
//                            'image_url',
//                            'video_url',
//                            'sound_url',
//                            'has_separator_left',
//                            'has_separator_right',
//                            'align',
//                            'on_click'
//                        ]
//                    ]
//                ],
//            ]
        ]
    ]
],
    $params['lang']
)
    ->adapt('json')
    ->first(true);

if (!$data) {
    throw new Exception("unknown_entity", QN_ERROR_INVALID_PARAM);
}

$context->httpResponse()
    ->body($data)
    ->send();
