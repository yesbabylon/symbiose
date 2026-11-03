<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Check;
use infra\server\Status;

[$params, $providers] = eQual::announce([
    'description'   => "Evaluate checks against their target's latest status.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Check',
            'description'       => 'Identifier of the targeted check.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\Check',
            'description'       => 'Identifiers of the targeted checks.',
            'default'           => []
        ]
    ],
    'access'        => [
        'visibility'    => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'self']
]);

/**
 * @var \equal\php\Context      $context
 * @var \equal\orm\Collection   $self
 */
['context' => $context, 'self' => $self] = $providers;

/**
 * Methods
 */

$flatten = function($array, $prefix = '') use(&$flatten) {
    $result = [];

    foreach($array as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if(is_array($value) && $value !== []) {
            $result += $flatten($value, $path);
        }
        else {
            $result[$path] = $value;
        }
    }

    return $result;
};

/**
 * Action
 */

if(empty($params['ids']) && $params['id'] <= 0) {
    throw new Exception('missing_check', EQ_ERROR_MISSING_PARAM);
}

$self->read([
    'object_class',
    'object_id',
    'status_path',
    'operator',
    'check_value',
    'failure_count'
]);

$status_id_by_target = [];
$status_data_by_target = [];
foreach($self as $id => $check) {
    $target_key = "{$check['object_class']}:{$check['object_id']}";
    if(!isset($status_data_by_target[$target_key])) {
        $latest_status = Status::search(
            [
                ['object_class', '=', $check['object_class']],
                ['object_id', '=', $check['object_id']]
            ],
            [
                'sort'  => ['created' => 'desc'],
                'limit' => 1
            ]
        )
            ->read(['status_data'])
            ->first();

        if(!$latest_status) {
            throw new Exception('unknown_status', EQ_ERROR_INVALID_PARAM);
        }

        $decoded_status_data = json_decode($latest_status['status_data'], true);
        if(!is_array($decoded_status_data)) {
            throw new Exception('invalid_status_data', EQ_ERROR_INVALID_PARAM);
        }

        $status_id_by_target[$target_key] = $latest_status['id'];
        $status_data_by_target[$target_key] = $flatten($decoded_status_data);
    }

    $status_data = $status_data_by_target[$target_key];
    $status_path_exists = array_key_exists($check['status_path'], $status_data);
    if(in_array($check['operator'], ['exists', 'not_exists'], true)) {
        $is_success = $check['operator'] === 'exists' ? $status_path_exists : !$status_path_exists;

        Check::id($id)->update([
            'value'             => json_encode($status_path_exists),
            'is_success'        => $is_success,
            'failure_count'     => $is_success ? 0 : $check['failure_count'] + 1,
            'last_status_id'    => $status_id_by_target[$target_key]
        ]);
        continue;
    }

    if(!$status_path_exists) {
        /*
        #memo - if the key doesn't exist then ignore (scope was probably not loaded)
        #memo - note: the key "state.up" is always present

        Check::id($id)->update([
            'is_success'    => false,
            'failure_count' => $check['failure_count'] + 1
        ]);
        */
        continue;
    }

    $value = $status_data[$check['status_path']];
    $check_value = $check['check_value'];
    switch(gettype($value)) {
        case 'integer':
            $check_value = (int) $check_value;
            break;
        case 'double':
            $check_value = (float) $check_value;
            break;
        case 'boolean':
            $check_value = in_array(strtolower($check_value), ['true', '1', 'yes'], true);
            break;
    }

    $is_success = false;
    switch($check['operator']) {
        case 'eq':
            $is_success = $value === $check_value;
            break;
        case 'ne':
            $is_success = $value !== $check_value;
            break;
        case 'gt':
            $is_success = $value > $check_value;
            break;
        case 'gte':
            $is_success = $value >= $check_value;
            break;
        case 'lt':
            $is_success = $value < $check_value;
            break;
        case 'lte':
            $is_success = $value <= $check_value;
            break;
        case 'contains':
        case 'does_not_contain':
            if(is_array($value)) {
                $contains = in_array($check_value, $value, true);
            }
            elseif(is_string($value)) {
                $contains = strpos($value, (string) $check_value) !== false;
            }
            else {
                break;
            }
            $is_success = $check['operator'] === 'contains' ? $contains : !$contains;
            break;
        case 'in':
        case 'not_in':
            $accepted_values = json_decode($check['check_value'], true);
            if(!is_array($accepted_values)) {
                $accepted_values = array_map('trim', explode(',', $check['check_value']));
            }

            foreach($accepted_values as $index => $accepted_value) {
                switch(gettype($value)) {
                    case 'integer':
                        $accepted_values[$index] = (int) $accepted_value;
                        break;
                    case 'double':
                        $accepted_values[$index] = (float) $accepted_value;
                        break;
                    case 'boolean':
                        $accepted_values[$index] = in_array(
                            strtolower((string) $accepted_value),
                            ['true', '1', 'yes'],
                            true
                        );
                        break;
                }
            }

            $in_values = in_array($value, $accepted_values, true);
            $is_success = $check['operator'] === 'in' ? $in_values : !$in_values;
            break;
    }

    Check::id($id)->update([
        'value'             => is_string($value) ? $value : json_encode($value),
        'is_success'        => $is_success,
        'failure_count'     => $is_success ? 0 : $check['failure_count'] + 1,
        'last_status_id'    => $status_id_by_target[$target_key]
    ]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
