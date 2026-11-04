<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Instance;
use infra\server\IpAddress;
use infra\server\Server;
use infra\server\Status;

[$params, $providers] = eQual::announce([
    'description'   => "Fetches and saves statuses for a given server.",
    'help'          => "Calls hosts API to fetch statuses and updates server 'up' field accordingly.",
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server.',
            'default'           => 0
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifiers of the targeted servers.',
            'default'           => []
        ],
        'scopes' => [
            'type'              => 'array',
            'description'       => 'The scopes of data that needs to be returned.',
            'help'              => 'Scopes are `instant`, `state` or `config`. If empty all scopes loaded.',
            'default'           => []
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
    'instances_ids',
    'checks_ids',
    'alert_triggers_ids',
    'ip_address_ids' => ['ip_v4', 'ip_v6', 'visibility', 'exposure']
]);

foreach($self as $id => $server) {
    $status = null;
    $error = null;
    try {
        $get_status_params = ['id' => $id];
        if(!empty($scopes)) {
            $get_status_params['scopes'] = $scopes;
        }

        $map_servers_statuses = eQual::run('get', 'infra_server_Server_status', $get_status_params);

        if(!empty($map_servers_statuses[$id]['error'])) {
            $error = $map_servers_statuses[$id]['error'];
        }
        else {
            $status = $map_servers_statuses[$id];
        }
    }
    catch(Exception $e) {
        trigger_error(
            "APP::Unable to synchronize status for server {$id}: {$e->getMessage()}",
            EQ_REPORT_WARNING
        );

        $error = $e->getMessage();
    }

    if($status) {
        // #memo - add state up in status_data for Alert
        $status['state']['up'] = true;

        $existing_ip_addresses = [];
        foreach($server['ip_address_ids'] as $ip_address) {
            foreach(['ip_v4', 'ip_v6'] as $ip_field) {
                if(!empty($ip_address[$ip_field])) {
                    $existing_ip_addresses[$ip_address[$ip_field]] = [
                        'id'            => $ip_address['id'],
                        'visibility'    => $ip_address['visibility'],
                        'exposure'      => $ip_address['exposure']
                    ];
                }
            }
        }

        $ip_config = [
            'ip_public' => [
                'visibility'    => 'public',
                'exposure'      => 'public'
            ],
            'ip_protected' => [
                'visibility'    => 'protected',
                'exposure'      => 'public'
            ],
            'ip_private' => [
                'visibility'    => 'private',
                'exposure'      => 'private'
            ]
        ];

        foreach($ip_config as $config_key => $ip_values) {
            if(empty($status['config'][$config_key])) {
                continue;
            }

            $addresses = is_array($status['config'][$config_key])
                ? $status['config'][$config_key]
                : [$status['config'][$config_key]];

            foreach($addresses as $address) {
                if(!is_string($address) || trim($address) === '') {
                    continue;
                }

                $address = trim($address);
                $address_without_prefix = explode('/', $address)[0];
                if(filter_var($address_without_prefix, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ip_field = 'ip_v4';
                }
                elseif(filter_var($address_without_prefix, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ip_field = 'ip_v6';
                }
                else {
                    trigger_error("APP::Ignoring invalid IP address '{$address}' for server {$id}.", EQ_REPORT_WARNING);
                    continue;
                }

                if(isset($existing_ip_addresses[$address])) {
                    $existing_ip_address = $existing_ip_addresses[$address];
                    $updates = [];
                    foreach($ip_values as $field => $value) {
                        if($existing_ip_address[$field] !== $value) {
                            $updates[$field] = $value;
                        }
                    }
                    if($updates) {
                        IpAddress::id($existing_ip_address['id'])->update($updates);
                        $existing_ip_addresses[$address] = array_merge($existing_ip_address, $updates);
                    }
                    continue;
                }

                $ip_address = IpAddress::create(array_merge(
                    [
                        'server_id' => $id,
                        $ip_field   => $address
                    ],
                    $ip_values
                ))
                    ->first();

                $existing_ip_addresses[$address] = array_merge(
                    ['id' => $ip_address['id']],
                    $ip_values
                );
            }
        }

        Status::create([
            'object_class'  => Server::class,
            'object_id'     => $id,
            'status_data'   => json_encode($status, JSON_PRETTY_PRINT)
        ]);

        // server is up
        Server::id($id)->update([
            'up'        => true,
            'synced'    => time()
        ]);

        if($params['sync_instances']) {
            foreach($server['instances_ids'] as $instance_id) {
                eQual::run('do', 'infra_server_Instance_sync-status', [
                    'id'        => $instance_id,
                    'scopes'    => $scopes
                ]);
            }
        }
    }
    else {
        $status = ['state' => ['up' => false]];
        if(!empty($error)) {
            $status['error'] = $error;
        }

        Status::create([
            'object_class'      => Server::class,
            'object_id'         => $id,
            'status_data'       => json_encode($status, JSON_PRETTY_PRINT),
            'is_collection_ok'  => false
        ]);

        // server is down (will cascade to instances)
        Server::id($id)->update([
            'up'        => false,
            'synced'    => time()
        ]);

        if($params['sync_instances']) {
            foreach($server['instances_ids'] as $instance_id) {
                Status::create([
                    'object_class'      => Instance::class,
                    'object_id'         => $instance_id,
                    'status_data'       => json_encode($status, JSON_PRETTY_PRINT),
                    'is_collection_ok'  => false
                ]);

                Instance::id($instance_id)->update([
                    'up'        => false,
                    'synced'    => time()
                ]);
            }
        }
    }

    if(!empty($server['checks_ids'])) {
        eQual::run('do', 'infra_server_Check_evaluate-latest-status', ['ids' => $server['checks_ids']]);

        if(!empty($server['alert_triggers_ids'])) {
            eQual::run('do', 'infra_server_AlertTrigger_process', ['ids' => $server['alert_triggers_ids']]);
        }
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
