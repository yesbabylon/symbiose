<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;

[$params, $providers] = eQual::announce([
    'description'   => 'Fetches instant statuses of servers.',
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
        ]
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

$self->read(['server_type', 'accesses_ids']);

$map_servers_accesses = [];
foreach($self as $id => $server) {
    if(!in_array($server['server_type'], ['b2', 'k2', 's2', 'admin'])) {
        throw new Exception('server_type_not_handled', EQ_ERROR_NOT_ALLOWED);
    }

    $accesses = Access::search([
        ['server_id', '=', $server['id']],
        ['access_type', 'in', ['https', 'http']]
    ])
        ->read(['port', 'url', 'password'])
        ->get(true);

    $access = null;
    foreach($accesses as $ac) {
        if($ac['port'] === '8000') {
            $access = $ac;
        }
    }

    // #memo - fallback on first https if not found with port 8000
    if(!$access) {
        $access = $accesses[0];
    }

    if(!$access) {
        throw new Exception('missing_b2_host_access');
    }

    $map_servers_accesses[$id] = $access;
}

$result = [];
foreach($self as $id => $server) {
    $access = $map_servers_accesses[$id];

    $request = new HttpRequest("GET {$access['url']}/status");

    $credentials = base64_encode("root:{$access['password']}");
    $request->setHeader('Authorization', "Basic $credentials");

    $response = $request->send();

    $result[$id] = $response->body();
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
