<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\User;
use timetrack\Project;
use timetrack\TimeEntry;
use timetrack\TimeEntrySaleModel;

list($params, $providers) = eQual::announce([
    'description'    => 'Quick create a time entry with minimal information.',
    'params'         => [
        'user_id'    => [
            'type'           => 'many2one',
            'foreign_object' => 'core\User',
            'description'    => 'User who realised the time entry.',
            'required'       => true
        ],

        'project_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'timetrack\Project',
            'description'    => 'Time entry project.',
            'required'       => true
        ],

        'origin'     => [
            'type'           => 'string',
            'selection'      => [
                'project',
                'backlog',
                'email',
                'support'
            ],
            'description'    => 'Time entry origin.',
            'required'       => true
        ]
    ],
    'response'       => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'      => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

$user = User::id($params['user_id'])
    ->read(['id'])
    ->first();

if(!isset($user)) {
    throw new Exception('unknown_user', QN_ERROR_UNKNOWN_OBJECT);
}

$project = Project::id($params['project_id'])
    ->read(['id'])
    ->first();

if(!isset($project)) {
    throw new Exception('unknown_project', QN_ERROR_UNKNOWN_OBJECT);
}

$sale_model = null;
if(isset($params['origin'], $params['project_id'])) {
    $sale_model = TimeEntrySaleModel::getModelToApply(
        $params['origin'],
        $params['project_id']
    );
}

TimeEntry::create([
    'description' => 'New entry '.date('Y-m-d H:m:s', time()),
    'user_id'     => $params['user_id'],
    'object_id'   => $params['project_id'],
    'project_id'  => $params['project_id'],
    'origin'      => $params['origin'],
    'product_id'  => $sale_model['product_id'] ?? null,
    'price_id'    => $sale_model['price_id'] ?? null,
    'unit_price'  => $sale_model['unit_price'] ?? null,
    'is_billable' => !is_null($sale_model)
]);

$context->httpResponse()
        ->status(204)
        ->send();
