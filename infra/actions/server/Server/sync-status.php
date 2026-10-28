<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Server;
use infra\server\ServerStatus;

[$params, $providers] = eQual::announce([
    'description'   => "Fetches and saves statuses for a given server.",
    'help'          => "Calls hosts API to fetch 'instant' statuses and updates server 'up' field accordingly.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server',
            'required'          => true
        ],
        'sync_instances' => [
            'type'              => 'boolean',
            'description'       => 'Synchronize statuses of all the instances of the server.',
            'default'           => false
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

$server = Server::id($params['id'])
    ->read(['instances_ids'])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_INVALID_PARAM);
}

try {
    $status = eQual::run('get', 'infra_server_Server_status', ['id' => $server['id']]);

    $ips_data = [];
    foreach(['ip_protected', 'ip_public', 'ip_private'] as $ip_key) {
        if(!empty($status['config'][$ip_key])) {
            $ips_data[$ip_key] = $status['config'][$ip_key];
        }
    }
    if(!empty($ips_data)) {
        Server::id($server['id'])->update($ips_data);
    }

    ServerStatus::create([
        'server_id'     => $server['id'],
        'status_data'   => json_encode($status, JSON_PRETTY_PRINT),
        'dsk_use'       => (float) str_replace(['%', ','], ['', '.'], $status['instant']['dsk_use'] ?? 0) / 100,
        'cpu_use'       => (float) str_replace(['%', ','], ['', '.'], $status['instant']['cpu_use'] ?? 0) / 100,
        'ram_use'       => (float) str_replace(['%', ','], ['', '.'], $status['instant']['ram_use'] ?? 0) / 100,
        'total_proc'    => intval($status['instant']['total_proc'] ?? 0)
    ]);

    // server is up
    Server::id($server['id'])->update(['up' => true, 'synced' => time()]);

    if($params['sync_instances']) {
        foreach($server['instances_ids'] as $instance_id) {
            eQual::run('do', 'infra_server_Instance_sync-status', ['id' => $instance_id, 'scope' => 'instant']);
        }
    }
}
catch(Exception $e) {
    trigger_error(
        "APP::Unable to synchronize status for server {$server['id']}: {$e->getMessage()}",
        EQ_REPORT_WARNING
    );

    // server is down (will cascade to instances)
    Server::id($params['id'])->update(['up' => false, 'synced' => time()]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
