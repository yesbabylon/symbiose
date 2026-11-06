<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Check;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'description'   => 'Create a server check from an example check.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server.',
            'required'          => true
        ],
        'check_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Check',
            'description'       => 'Example check to copy to the server.',
            'domain'            => [
                ['object_class', '=', Server::class],
                ['object_id', '=', null]
            ],
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

$server = Server::id($params['id'])
    ->read(['id'])
    ->first();

if(!$server) {
    throw new Exception('unknown_server', EQ_ERROR_UNKNOWN_OBJECT);
}

$template = Check::search([
    ['id', '=', $params['check_id']],
    ['object_class', '=', Server::class],
    ['object_id', '=', null]
])
    ->read([
        'code',
        'description',
        'status_path',
        'operator',
        'check_value'
    ])
    ->first();

if(!$template) {
    throw new Exception('unknown_check_template', EQ_ERROR_UNKNOWN_OBJECT);
}

$code = "{$template['code']}-server-{$server['id']}";
$existing_check = Check::search([
    ['object_class', '=', Server::class],
    ['object_id', '=', $server['id']],
    ['code', 'like', "{$code}%"]
]);
if($existing_check->count() > 0) {
    throw new Exception('check_template_already_implemented', EQ_ERROR_CONFLICT_OBJECT);
}

$check = Check::create([
    'code'          => $code,
    'description'   => $template['description'],
    'object_class'  => Server::class,
    'object_id'     => $server['id'],
    'status_path'   => $template['status_path'],
    'operator'      => $template['operator'],
    'check_value'   => $template['check_value'],
    'value'         => ''
])
    ->read(['id'])
    ->first();

$context
    ->httpResponse()
    ->status(201)
    ->send();
