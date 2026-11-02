<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => "Evaluate all servers checks and instances checks against latest statuses.",
    'params'        => [],
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

$servers = Server::search([
    ['server_type', 'in', ['b2', 'k2', 's2', 'admin']]
])
    ->get();

foreach($servers as $server_id => $server) {
    eQual::run('do', 'infra_server_Server_check-statuses', [
        'id'                => $server_id,
        'check_instances'   => true
    ]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
