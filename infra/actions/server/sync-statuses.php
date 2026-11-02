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
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server',
            'required'          => true
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

$servers = Server::search([
    ['server_type', 'in', ['b2', 'k2', 's2', 'admin']]
])
    ->read(['server_type', 'instances_ids'])
    ->get();

foreach($servers as $id => $server) {
    eQual::run('do', 'infra_server_Server_sync-status', [
        'id'                => $id,
        'sync_instances'    => true
    ]);
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
