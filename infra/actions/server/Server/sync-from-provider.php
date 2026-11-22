<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => 'Synchronize a server identifier and IP addresses from its cloud project after provisioning.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the local server to synchronize.',
            'required'          => true
        ],
        'operation_id' => [
            'type'              => 'string',
            'description'       => 'Identifier of the cloud provider instance creation operation. Required when the server has no external identifier yet.'
        ],
        'attempt' => [
            'type'              => 'integer',
            'description'       => 'Current synchronization attempt.'
        ],
        'retry_on_failure' => [
            'type'              => 'boolean',
            'description'       => 'Schedule another synchronization attempt when the current attempt fails.'
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
    ->read([
        'cloud_project_id' => ['cloud_provider_code']
    ])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

$cloud_provider_code = $server['cloud_project_id']['cloud_provider_code'];

$controllers = [
    'ovh' => 'infra_server_Server_ovh_sync-from-provider'
];

if(!isset($controllers[$cloud_provider_code])) {
    throw new Exception('unknown_cloud_provider', EQ_ERROR_INVALID_PARAM);
}

eQual::run('do', $controllers[$cloud_provider_code], $params, true, true);

$context
    ->httpResponse()
    ->send();

