<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\http\HttpRequest;
use infra\Access;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => 'Fetches instant status of a given server.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server',
            'required'          => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context']
]);

['context' => $context] = $providers;

$server = Server::id($params['id'])
    ->read(['server_type', 'accesses_ids'])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

if($server['server_type'] !== 'b2') {
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

$request = new HttpRequest("GET {$access['url']}/status");

$credentials = base64_encode("root:{$access['password']}");
$request->setHeader('Authorization', "Basic $credentials");

$response = $request->send();

$result = $response->body();

$context
    ->httpResponse()
    ->body($result)
    ->send();
