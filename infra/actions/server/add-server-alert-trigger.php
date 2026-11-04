<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\AlertTrigger;
use infra\server\Check;
use infra\server\Server;

[$params, $providers] = eQual::announce([
    'type'          => 'do',
    'name'          => 'add-server-alert-trigger',
    'package_name'  => 'infra',
    'description'   => 'Create a server alert trigger and its checks from an example.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\Server',
            'description'       => 'Identifier of the targeted server.',
            'required'          => true
        ],
        'alert_trigger_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'infra\server\AlertTrigger',
            'description'       => 'Example alert trigger to copy to the server.',
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

$template = AlertTrigger::search([
        ['id', '=', $params['alert_trigger_id']],
        ['object_class', '=', Server::class],
        ['object_id', '=', null]
    ])
    ->read([
        'scope',
        'repetition',
        'user_id',
        'group_id',
        'message_model_id',
        'checks_ids' => [
            'code',
            'object_class',
            'object_id',
            'status_path',
            'operator',
            'check_value'
        ]
    ])
    ->first();

if(!$template) {
    throw new Exception('unknown_alert_trigger_template', EQ_ERROR_UNKNOWN_OBJECT);
}
if($template['checks_ids']->count() === 0) {
    throw new Exception('empty_alert_trigger_template', EQ_ERROR_INVALID_CONFIG);
}

foreach($template['checks_ids'] as $check_template) {
    if($check_template['object_class'] !== Server::class || !is_null($check_template['object_id'])) {
        throw new Exception('invalid_alert_trigger_template', EQ_ERROR_INVALID_CONFIG);
    }
}

$checks_ids = [];
foreach($template['checks_ids'] as $check_template) {
    $base_code = "{$check_template['code']}-server-{$server['id']}";
    $code = $base_code;
    $suffix = 2;
    while(Check::search(['code', '=', $code])->count() > 0) {
        $code = "{$base_code}-{$suffix}";
        ++$suffix;
    }

    $descriptions = [];
    foreach(['en', 'fr'] as $lang) {
        $localized_check_template = Check::id($check_template['id'])
            ->read(['description'], $lang)
            ->first();

        $descriptions[$lang] = $localized_check_template['description'];
    }

    $check = Check::create(
        [
            'code'          => $code,
            'description'   => $descriptions['en'],
            'object_class'  => Server::class,
            'object_id'     => $server['id'],
            'status_path'   => $check_template['status_path'],
            'operator'      => $check_template['operator'],
            'check_value'   => $check_template['check_value'],
            'value'         => ''
        ],
        'en'
    );

    $check->update(['description' => $descriptions['fr']], 'fr');

    $check = $check->read(['id'])->first();

    $checks_ids[] = $check['id'];
}

AlertTrigger::create([
    'object_class'      => Server::class,
    'object_id'         => $server['id'],
    'scope'             => $template['scope'],
    'repetition'        => $template['repetition'],
    'checks_ids'        => $checks_ids,
    'user_id'           => $template['user_id'],
    'group_id'          => $template['group_id'],
    'message_model_id'  => $template['message_model_id']
]);

$context
    ->httpResponse()
    ->status(201)
    ->send();
