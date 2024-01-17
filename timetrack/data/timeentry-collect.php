<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\Setting;
use equal\orm\Domain;
use timetrack\TimeEntry;

$time_zone = Setting::get_value('core', 'locale', 'time_zone');

list($params, $providers) = eQual::announce([
    'description'   => 'Advanced search for Time Entries: returns a collection of Reports according to extra parameters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' => [
            'description'    => 'Full name (including namespace) of the class to return.',
            'type'           => 'string',
            'default'        => 'timetrack\TimeEntry'
        ],

        'name' => [
            'description'    => 'Display only entries matching the given name',
            'type'           => 'string'
        ],

        'user_id' => [
            'description'    => 'Display only entries of selected user',
            'type'           => 'many2one',
            'foreign_object' => 'core\User'
        ],

        'time_start' => [
            'description'    => 'Display only entries who start on and after selected time',
            'type'           => 'datetime',
            'default'        => strtotime('first day of last month 00:00:00 '.$time_zone, time())
        ],

        'time_end' => [
            'description'    => 'Display only entries who end on and before selected time',
            'type'           => 'datetime',
            'default'        => strtotime('last day of this month 23:59:59 '.$time_zone, time())
        ],

        'customer_id' => [
            'description'    => 'Display only entries of selected customer',
            'type'           => 'many2one',
            'foreign_object' => 'sale\customer\Customer'
        ],

        'project_id' => [
            'description'    => 'Display only entries of selected project',
            'type'           => 'many2one',
            'foreign_object' => 'timetrack\Project'
        ],

        'origin' => [
            'description' => 'Display only entries of selected origin',
            'type'        => 'string',
            'selection'   => array_merge(['all'], array_keys(TimeEntry::ORIGIN_MAP)),
            'default'     => 'all'
        ],

        'has_receivable' => [
            'description' => 'Filter entries on has receivable',
            'type'        => 'boolean',
        ],

        'is_billable' => [
            'description' => 'Filter entries on is billable',
            'type'        => 'boolean',
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

if(isset($params['name']) && strlen($params['name']) > 0) {
    $params['domain'] = Domain::conditionAdd($params['domain'], ['name', 'ilike', '%'.$params['name'].'%']);
}

$relation_fields = ['customer_id', 'project_id', 'user_id'];
foreach($relation_fields as $field) {
    if(empty($params[$field])) {
        continue;
    }
    $params['domain'] = Domain::conditionAdd($params['domain'], [$field, '=', $params[$field]]);
}

if(isset($params['origin']) && $params['origin'] !== 'all') {
    $params['domain'] = Domain::conditionAdd($params['domain'], ['origin', '=', $params['origin']]);
}

if(isset($params['time_start'])) {
    $params['domain'] = Domain::conditionAdd($params['domain'], ['time_start', '>=', $params['time_start']]);
}

if(isset($params['time_end'])) {
    $params['domain'] = Domain::conditionAdd($params['domain'], ['time_end', '<=', $params['time_end']]);
}

$boolean_fields = ['is_billable', 'has_receivable'];
foreach($boolean_fields as $field) {
    if(!isset($params[$field])) {
        continue;
    }
    $params['domain'] = Domain::conditionAdd($params['domain'], [$field, '=', $params[$field]]);
}

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
