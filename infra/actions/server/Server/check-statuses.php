<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Evaluate server checks against latest statuses.",
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
        ],
        'check_instances' => [
            'type'              => 'boolean',
            'description'       => 'Evaluate checks of all the instances of the server.',
            'default'           => false
        ]
    ],
    'access'        => [
        'visibility'    => 'private'
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

$self->read(['instances_ids', 'checks_ids']);

foreach($self as $id => $server) {
    if($server['checks_ids']) {
        eQual::run('do', 'infra_server_Check_evaluate-latest-status', [
            'ids' => $server['checks_ids']
        ]);
    }

    if($params['check_instances']) {
        foreach($server['instances_ids'] as $instance_id) {
            eQual::run('do', 'infra_server_Instance_check-statuses', ['id' => $instance_id]);
        }
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
