<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Check;
use infra\server\Instance;

[$params, $providers] = eQual::announce([
    'description'   => 'Create an instance check from an example check.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Instance',
            'description'       => 'Identifier of the targeted instance.',
            'required'          => true
        ],
        'check_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Check',
            'description'       => 'Example check to copy to the instance.',
            'domain'            => [
                ['object_class', '=', Instance::class],
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

$instance = Instance::id($params['id'])
    ->read(['id'])
    ->first();

if(!$instance) {
    throw new Exception('unknown_instance', EQ_ERROR_UNKNOWN_OBJECT);
}

$template = Check::search([
    ['id', '=', $params['check_id']],
    ['object_class', '=', Instance::class],
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

$base_code = "{$template['code']}-instance-{$instance['id']}";
$code = $base_code;
$suffix = 2;
while(Check::search(['code', '=', $code])->count() > 0) {
    $code = "{$base_code}-{$suffix}";
    ++$suffix;
}

$check = Check::create([
    'code'          => $code,
    'description'   => $template['description'],
    'object_class'  => Instance::class,
    'object_id'     => $instance['id'],
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
