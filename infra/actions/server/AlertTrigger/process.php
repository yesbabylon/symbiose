<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'       => "Processes the alert triggers to generate the alerts when checks fail.",
    'params'            => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\AlertTrigger',
            'description'       => 'Identifier of the targeted alert trigger.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\AlertTrigger',
            'description'       => 'Identifiers of the targeted alert triggers.',
            'default'           => []
        ]
    ],
    'response'          => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'         => ['context', 'self', 'dispatch']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\Collection       $self
 * @var \equal\dispatch\Dispatcher  $dispatch
 */
['context' => $context, 'self' => $self, 'dispatch' => $dispatch] = $providers;

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
    throw new Exception('missing_alert_trigger', EQ_ERROR_MISSING_PARAM);
}

$self->read([
    'object_class',
    'object_id',
    'user_id',
    'group_id',
    'repetition',
    'checks_ids' => [
        'is_success',
        'checked',
        'failure_count',
        'last_status_id' => [
            'status_data'
        ]
    ],
    'message_model_id' => [
        'name'
    ]
]);

foreach($self as $id => $alertTrigger) {
    $last_failed_check = null;
    foreach($alertTrigger['checks_ids'] as $check) {
        if(!$check['last_status_id'] || $check['is_success'] || $check['failure_count'] < $alertTrigger['repetition']) {
            continue;
        }

        if(!$last_failed_check || $last_failed_check['checked'] < $check['checked']) {
            $last_failed_check = $check;
        }
    }

    if(!$last_failed_check) {
        $dispatch->cancel($alertTrigger['message_model_id']['name'], $alertTrigger['object_class'], $alertTrigger['object_id']);
        continue;
    }

    $decoded_status_data = json_decode($last_failed_check['last_status_id']['status_data'], true);

    $values = [];
    foreach($flatten($decoded_status_data) as $key => $value) {
        $values["status_data.$key"] = $value;
    }

    $dispatch->emit($alertTrigger['message_model_id']['name'], $alertTrigger['object_class'], $alertTrigger['object_id'], [
        'severity'  => 'important',
        'user_id'   => $alertTrigger['user_id'],
        'group_id'  => $alertTrigger['group_id'],
        'values'    => $values
    ]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
