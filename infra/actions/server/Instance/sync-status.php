<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Instance;
use infra\server\Status;

[$params, $providers] = eQual::announce([
    'type'          => 'do',
    'name'          => 'sync-status',
    'package_name'  => 'infra',
    'description'   => "Fetches and saves statuses for a given instance.",
    'help'          => "Calls hosts API to fetch statuses and updates instance 'up' field accordingly.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifier of the targeted instance.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifiers of the targeted instances.',
            'default'           => []
        ],
        'scopes' => [
            'type'              => 'array',
            'description'       => 'The scopes of data that needs to be returned.',
            'help'              => 'Scopes are `instant`, `state` or `config`.',
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

if(empty($params['ids']) && $params['id'] <= 0) {
    throw new Exception('missing_check', EQ_ERROR_MISSING_PARAM);
}

$allowed_scopes = ['instant', 'state', 'config'];
$map_scopes = [];
foreach($params['scopes'] as $scope) {
    if(!is_string($scope) || !in_array($scope, $allowed_scopes, true)) {
        throw new Exception('invalid_scope', EQ_ERROR_INVALID_PARAM);
    }

    $map_scopes[$scope] = true;
}

if(!empty($map_scopes)) {
    $map_scopes['state'] = true;
}
$scopes = array_keys($map_scopes);

$self->read([
    'b2_instance_type',
    'checks_ids',
    'alert_triggers_ids',
    'server_id' => [
        'server_type'
    ]
]);

foreach($self as $id => $instance) {
    if($instance['server_id']['server_type'] !== 'b2') {
        throw new Exception('server_type_not_handled', EQ_ERROR_INVALID_PARAM);
    }
}

foreach($self as $id => $instance) {
    $get_status_params = [
        'id' => $instance['id']
    ];
    if(!empty($scopes) && !empty($instance['b2_instance_type'])) {
        $get_status_params['scopes'] = $scopes;
    }

    $b2_instance_status = null;
    $error = null;
    try {
        $map_instances_statuses = eQual::run('get', 'infra_server_Instance_status', $get_status_params);

        if(!empty($map_instances_statuses[$id]['error'])) {
            $error = $map_instances_statuses[$id]['error'];
        }
        else {
            $b2_instance_status = $map_instances_statuses[$id];
        }
    }
    catch(Exception $e) {
        trigger_error(
            "APP::Unable to synchronize status for instance {$instance['id']}: {$e->getMessage()}",
            EQ_REPORT_WARNING
        );

        $error = $e->getMessage();
    }

    if($b2_instance_status) {
        // #memo - add state up in status_data for Alert
        $b2_instance_status['state']['up'] = true;

        Status::create([
            'object_class'  => Instance::class,
            'object_id'     => $instance['id'],
            'status_data'   => json_encode($b2_instance_status, JSON_PRETTY_PRINT)
        ]);

        $instance_data = [
            'up'        => true,
            'synced'    => time()
        ];
        if(!empty($b2_instance_status['config']['type'])) {
            $instance_data['b2_instance_type'] = $b2_instance_status['config']['type'];
        }
        $branches = [];
        foreach($b2_instance_status['config']['sources'] ?? [] as $source) {
            if(!is_array($source) || empty($source['repository']) || empty($source['branch'])) {
                continue;
            }

            $repository_path = parse_url($source['repository'], PHP_URL_PATH) ?: $source['repository'];
            $repository_name = preg_replace('/\.git$/i', '', basename(rtrim($repository_path, '/')));
            if(empty($repository_name)) {
                continue;
            }

            $branches[$repository_name] = $source['branch'];
        }

        if(count($branches) === 1) {
            $instance_data['branch'] = array_values($branches)[0];
        }
        elseif(count($branches) > 1) {
            $instance_data['branch'] = json_encode($branches);
        }

        // instance is up
        Instance::id($instance['id'])->update($instance_data);
    }
    else {
        $status = ['state' => ['up' => false]];
        if(!empty($error)) {
            $status['error'] = $error;
        }

        Status::create([
            'object_class'      => Instance::class,
            'object_id'         => $instance['id'],
            'status_data'       => json_encode($status, JSON_PRETTY_PRINT),
            'is_collection_ok'  => false
        ]);

        // instance is down
        Instance::id($instance['id'])->update([
            'up'        => false,
            'synced'    => time()
        ]);
    }

    if(!empty($instance['checks_ids'])) {
        eQual::run('do', 'infra_server_Check_evaluate-latest-status', ['ids' => $instance['checks_ids']]);

        if(!empty($instance['alert_triggers_ids'])) {
            eQual::run('do', 'infra_server_AlertTrigger_process', ['ids' => $instance['alert_triggers_ids']]);
        }
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
