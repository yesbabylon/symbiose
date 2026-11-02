<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;

[$params, $providers] = eQual::announce([
    'description'   => 'Fetches instant status of instances.',
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
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

/**
 * @var \equal\php\Context      $context
 * @var \equal\orm\Collection   $self
 */
['context' => $context, 'self' => $self] = $providers;

$self->read(['name', 'server_id' => ['server_type']]);

$map_instances_accesses = [];
foreach($self as $id => $instance) {
    if(!in_array($instance['server_id']['server_type'], ['b2', 'k2', 's2', 'admin'])) {
        throw new Exception('server_type_not_handled', EQ_ERROR_NOT_ALLOWED);
    }

    $accesses = Access::search([
        ['server_id', '=', $instance['server_id']['id']],
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

    $map_instances_accesses[$id] = $access;
}

$result = [];
foreach($self as $id => $instance) {
    $access = $map_instances_accesses[$id];

    $request_params = ['instance' => $instance['name']];
    if($params['scope'] !== 'all') {
        $request_params['scope'] = $params['scope'];
    }

    $request = new HttpRequest("GET {$access['url']}/instance/status?".http_build_query($request_params));

    $credentials = base64_encode("root:{$access['password']}");
    $request->setHeader('Authorization', "Basic $credentials");

    $response = $request->send();

    $result = $response->body();
}

$context
    ->httpResponse()
    ->body($result)
    ->send();
