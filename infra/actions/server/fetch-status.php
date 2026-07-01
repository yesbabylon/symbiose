<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Server;
use infra\server\Status;

[$params, $providers] = eQual::announce([
    'description'       => "Fetches and saves statuses for a given server.",
    'help'              => "Calls hosts API to fetch 'instant' statuses and updates server 'up' field accordingly.",
    'params'            => [
        'id' =>  [
            'description'       => 'Identifier of the targeted server',
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'required'          => true
        ]
    ],
    'access'            => [
        'visibility'        => 'private'
    ],
    'response'          => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'         => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
['context' => $context] = $providers;

$server = Server::id($params['id'])->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_INVALID_PARAM);
}

try {
    $status = equal::run('get', 'infra_server_status', ['id' => $params['id']]);

    $values = [
        'server_id'     => $params['id'],
        'status_data'   => json_encode($status, JSON_PRETTY_PRINT),
        'dsk_use'       => (float) str_replace(['%', ','], ['', '.'], $status['dsk_use'] ?? 0) / 100,
        'cpu_use'       => (float) str_replace(['%', ','], ['', '.'], $status['cpu_use'] ?? 0) / 100,
        'ram_use'       => (float) str_replace(['%', ','], ['', '.'], $status['ram_use'] ?? 0) / 100,
        'total_proc'    => intval($status['total_proc'] ?? 0)
    ];

    Status::create($values);

    // server is up
    Server::id($params['id'])->update(['up' => true, 'synced' => time()]);
}
catch(Exception $e) {
    // server is down (will cascade to instances)
    Server::id($params['id'])->update(['up' => false, 'synced' => time()]);
}


$context->httpResponse()
        ->status(204)
        ->send();
