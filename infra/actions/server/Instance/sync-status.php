<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Instance;
use infra\server\Status;

[$params, $providers] = eQual::announce([
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
        'scope' => [
            'type'              => 'string',
            'description'       => 'The scope of data that needs to be returned.',
            'selection'         => [
                'all',
                'instant',
                'state',
                'config'
            ],
            'default'           => 'all'
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

$self->read([
    'url',
    'b2_instance_type',
    'server_id' => [
        'server_type'
    ]
]);

foreach($self as $id => $instance) {
    if(empty($instance['url'])) {
        throw new Exception('missing_api_url', EQ_ERROR_INVALID_PARAM);
    }

    if($instance['server_id']['server_type'] !== 'b2') {
        throw new Exception('server_type_not_handled', EQ_ERROR_INVALID_PARAM);
    }
}

foreach($self as $id => $instance) {
    $get_status_params = [
        'id' => $instance['id']
    ];
    if($params['scope'] !== 'all' && !empty($instance['b2_instance_type'])) {
        $get_status_params['scope'] = $params['scope'];
    }

    $b2_instance_status = null;
    $error = null;
    try {
        $map_servers_statuses = eQual::run('get', 'infra_server_Instance_status', $get_status_params);

        $b2_instance_status = $map_servers_statuses[$id];
    }
    catch(Exception $e) {
        trigger_error(
            "APP::Unable to synchronize status for instance {$instance['id']}: {$e->getMessage()}",
            EQ_REPORT_WARNING
        );

        $error = [
            'code'      => $e->getCode(),
            'message'   => $e->getMessage()
        ];
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
        if(!empty($b2_instance_status['config']['branches'])) {
            $instance_data['branch'] = !empty($b2_instance_status['config']['branches']['packages']) ? json_encode($b2_instance_status['config']['branches']) : $b2_instance_status['config']['branches']['root'];
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
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
