<?php
/*
    This file is part of the Discope property management software.
    Author: Yesbabylon SRL, 2020-2022
    License: GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\Domain;
use timetrack\TimeEntry;

$filters = [
    'name' => [
        'description'    => 'Display only entries matching the given name',
        'type'           => 'string'
    ],
    'user_id' => [
        'description'    => 'Display only entries of selected user',
        'type'           => 'many2one',
        'foreign_object' => 'core\User'
    ],
    'show_filter_date' => [
        'type'           => 'boolean',
        'default'        => false
    ],
    'date' => [
        'description'    => 'Display only entries that occurred on selected date',
        'type'           => 'date',
        'default'        => strtotime('today')
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
        'description'    => 'Display only entries of selected origin',
        'type'           => 'string',
        'selection'      => array_merge(['all'], array_keys(TimeEntry::ORIGIN_MAP)),
        'default'        => 'all'
    ],
    'has_receivable' => [
        'description'    => 'Filter entries on has receivable',
        'type'           => 'boolean',
    ],
    'is_billable' => [
        'description'    => 'Filter entries on is billable',
        'type'           => 'boolean',
    ]
];

list($params, $providers) = eQual::announce([
    'description' => 'Advanced search for Time Entries: returns a collection of Reports according to extra parameters.',
    'extends'     => 'core_model_collect',
    'params'      => array_merge(
        [
            'entity' => [
                'description' => 'Full name (including namespace) of the class to return.',
                'type'        => 'string',
                'default'     => 'timetrack\TimeEntry'
            ],
        ],
        $filters
    ),
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

if(!$params['show_filter_date']) {
    unset($filters['date']);
}
unset($filters['show_filter_date']);

$domain = [];
foreach($filters as $field => $field_config) {
    $value = $params[$field];

    $type = $field_config['type'];
    if($type === 'string' && !empty($field_config['selection'])) {
        $type = 'selection';
    }

    if($type === 'string' && strlen($value ?? '') > 0) {
        $domain[] = [$field, 'ilike', '%'.$value.'%'];
    }
    elseif(
        ($type === 'many2one' && !empty($value))
        || ($type === 'selection' && ($value ?? 'all') !== 'all')
        || (in_array($type, ['boolean', 'date']) && isset($value))
    ) {
        $domain[] = [$field, '=', $value];
    }
}

$params['domain'] = (new Domain($params['domain']))
    ->merge(new Domain($domain))
    ->toArray();

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
