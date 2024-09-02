<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use core\User;
use timetrack\Project;
use timetrack\TimeEntry;

list($params, $providers) = eQual::announce([
    'description'    => 'Quick create a time entry with minimal information.',
    'params'         => [
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
        ],

        'reference'     => [
            'type'           => 'string',
            'description'    => 'Reference completing the origin.'
        ],

        'description'     => [
            'type'           => 'string',
            'description'    => 'Short description.',
            'required'       => true
        ],

        'date'        => [
            'type'           => 'date',
            'description'    => 'Date on which the task was performed.',
            'default'        => function () { return time(); }
        ],

        'duration'        => [
            'type'           => 'time',
            'description'    => 'Task duration.',
            'default'        => 900
        ]

    ],
    'response'       => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'providers'      => ['context', 'auth']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\auth\AuthenticationManager $auth
 */
list($context, $auth) = [ $providers['context'], $providers['auth'] ];

$user_id = $auth->userId();

if($user_id <= 0) {
    throw new Exception('unknown_user', EQ_ERROR_NOT_ALLOWED);
}

$project = Project::id($params['project_id'])->first();

if(!isset($project)) {
    throw new Exception('unknown_project', EQ_ERROR_UNKNOWN_OBJECT);
}

// compute start time according to received duration and timezone set in config
$tz_offset = 0;

$time_zone = Setting::get_value('core', 'locale', 'time_zone');
if(!is_null($time_zone)) {
    $tz = new DateTimeZone($time_zone);
    // timezone offset in seconds to apply, depending on the date of the time entry
    $tz_offset = $tz->getOffset(new DateTime());
}

$date = $params['date'];
$begin = time() - strtotime("midnight") - $params['duration'] + $tz_offset;
$start = (int) (floor(floatval($begin) / 60 / 15) * 15 * 60);
$end = $start + intval(ceil($params['duration'] / 60 / 15) * 15 * 60);

TimeEntry::create([
        'project_id'  => $params['project_id'],
        'origin'      => $params['origin'],
        'reference'   => $params['reference'] ?? '',
        'description' => $params['description'],
        'date'        => $date,
        'time_start'  => $start,
        'time_end'    => $end
    ])
    ->transition('submit');

$context->httpResponse()
        ->status(201)
        ->send();
