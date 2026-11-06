<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => "Fetches and saves statuses for b2, k2, s2 and admin servers.",
    'params'        => [
        'scopes' => [
            'type'              => 'array',
            'description'       => 'The scopes of data that needs to be returned.',
            'help'              => 'Scopes are `instant`, `state` or `config`. If empty all scopes loaded.',
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
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$allowed_scopes = ['instant', 'state', 'config'];
$map_scopes = [];
foreach($params['scopes'] as $scope) {
    if(!is_string($scope) || !in_array($scope, $allowed_scopes, true)) {
        throw new Exception('invalid_scope', EQ_ERROR_INVALID_PARAM);
    }

    $map_scopes[$scope] = true;
}

$scopes = array_keys($map_scopes);

$servers_ids = Server::search([
    ['server_type', 'in', ['b2', 'k2', 's2', 'admin']]
])
    ->ids();

foreach($servers_ids as $id) {
    eQual::run('do', 'infra_server_Server_sync-status', [
        'id'                => $id,
        'scopes'            => $scopes,
        'sync_instances'    => true
    ]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
