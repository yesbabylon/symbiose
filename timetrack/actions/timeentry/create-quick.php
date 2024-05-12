<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\User;
use timetrack\Project;
use timetrack\TimeEntry;

list($params, $providers) = eQual::announce([
    'description'    => 'Quick create a time entry with minimal information.',
    'params'         => [
        'user_id'    => [
            'type'           => 'many2one',
            'foreign_object' => 'core\User',
            'description'    => 'User who performed the time entry.',
            'required'       => true
        ],

        'project_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'timetrack\Project',
            'description'    => 'Time entry project.',
            'required'       => true
        ],

        'origin'     => [
            'type'           => 'string',
            'selection'      => [
                'project',
                'backlog',
                'email',
                'support'
            ],
            'description'    => 'Time entry origin.',
            'required'       => true
        ]
    ],
    'response'       => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'      => ['context', 'auth']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\auth\AuthenticationManager $auth
 */
list($context, $auth) = [ $providers['context'], $providers['auth'] ];

$user_id = $aut->userId();

if($user_id <= 0) {
    throw new Exception('unknown_user', EQ_ERROR_NOT_ALLOWED);
}

$project = Project::id($params['project_id'])
    ->read(['id'])
    ->first();

if(!isset($project)) {
    throw new Exception('unknown_project', EQ_ERROR_UNKNOWN_OBJECT);
}

TimeEntry::create([
        'description' => 'New entry '.date('Y-m-d H:m:s', time()),
        'project_id'  => $params['project_id'],
        'origin'      => $params['origin']
    ]);

$context->httpResponse()
        ->status(201)
        ->send();
