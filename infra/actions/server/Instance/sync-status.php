<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Instance;
use infra\server\InstanceStatus;

[$params, $providers] = eQual::announce([
    'description'   => "Fetches and saves statuses for a given instance.",
    'help'          => "Calls hosts API to fetch 'instant' statuses and updates instance 'up' field accordingly.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => "Identifier of the targeted instance.",
            'required'          => true
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
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$instance = Instance::id($params['id'])
    ->read(['url', 'b2_instance_type', 'server_id' => ['server_type']])
    ->first();

if(!$instance) {
    throw new Exception('unknown_instance', EQ_ERROR_INVALID_PARAM);
}

if(empty($instance['url'])) {
    throw new Exception('missing_api_url', EQ_ERROR_INVALID_PARAM);
}

if($instance['server_id']['server_type'] !== 'b2') {
    throw new Exception('server_type_not_handled', EQ_ERROR_INVALID_PARAM);
}

$get_status_params = [
    'id' => $instance['id']
];
if($params['scope'] !== 'all' && !empty($instance['b2_instance_type'])) {
    $get_status_params['scope'] = $params['scope'];
}

try {
    $b2_instance_status = eQual::run('get', 'infra_server_Instance_status', $get_status_params);

    InstanceStatus::create([
        'instance_id'   => $instance['id'],
        'status_data'   => json_encode($b2_instance_status, JSON_PRETTY_PRINT),
        'dsk_use'       => (float) str_replace(['%', ','], ['', '.'], $b2_instance_status['instant']['dsk_use'] ?? 0) / 100,
        'cpu_use'       => (float) str_replace(['%', ','], ['', '.'], $b2_instance_status['instant']['cpu_use'] ?? 0) / 100,
        'ram_use'       => (float) str_replace(['%', ','], ['', '.'], $b2_instance_status['instant']['ram_use'] ?? 0) / 100,
        'total_proc'    => intval($b2_instance_status['instant']['total_proc'] ?? 0)
    ]);

    $instance_data = [
        'up'        => true,
        'synced'    => time()
    ];
    if(!empty($b2_instance_status['config']['type'])) {
        $instance_data['b2_instance_type'] = $b2_instance_status['config']['type'];
    }
    if(!empty($b2_instance_status['config']['branches'])) {
        $instance_data['branch'] = json_encode($b2_instance_status['config']['branches']);
    }

    // instance is up
    Instance::id($instance['id'])->update($instance_data);
}
catch(Exception $e) {
    trigger_error(
        "APP::Unable to synchronize status for instance {$instance['id']}: {$e->getMessage()}",
        EQ_REPORT_WARNING
    );

    // instance is down
    Instance::id($instance['id'])->update(['up' => false]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
