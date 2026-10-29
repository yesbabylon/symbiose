<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => "Evaluate instance checks against latest statuses.",
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

$self->read(['checks_ids']);
foreach($self as $id => $instance) {
    if($instance['checks_ids']) {
        eQual::run('do', 'infra_server_Check_evaluate-latest-status', [
            'ids' => $instance['checks_ids']
        ]);
    }
}

$context
    ->httpResponse()
    ->status(204)
    ->send();
