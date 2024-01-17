<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\User;
use timetrack\TimeEntry;
use timetrack\TimeEntrySaleModel;

list($params, $providers) = eQual::announce([
    'description'    => 'Quick create a time entry with minimal information.',
    'params'         => [
        'user_id'    => [
            'type'           => 'integer',
            'description'    => 'ID of the user who realised that time entry.',
            'required'       => true
        ],

        'project_id' => [
            'type'           => 'many2one',
            'foreign_object' => 'timetrack\Project',
            'description'    => 'Time entry project.',
            'required'       => true
        ],

        'origin'     => [
            'type'           => 'integer',
            'selection'      => array_keys(TimeEntry::ORIGIN_MAP),
            'description'    => 'Time entry origin.',
            'required'       => true
        ]
    ],
    'response'       => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'      => ['context', 'orm']
]);

$context = $providers['context'];

$user = User::id($params['user_id'])
    ->read(['id'])
    ->first();

if(!isset($user)) {
    throw new Exception('unknown_user', QN_ERROR_UNKNOWN_OBJECT);
}

$sale_model = null;
if(isset($params['origin'], $params['project_id'])) {
    $sale_model = TimeEntrySaleModel::getModelToApply(
        $params['origin'],
        $params['project_id']
    );
}

TimeEntry::create([
    'name'       => 'New entry '.date('Y-m-d H:m:s', time()),
    'user_id'    => $params['user_id'],
    'object_id'  => $params['project_id'],
    'project_id' => $params['project_id'],
    'origin'     => $params['origin'],
    'product_id' => $sale_model['product_id'] ?? null,
    'price_id'   => $sale_model['price_id'] ?? null,
    'unit_price' => $sale_model['unit_price'] ?? null,
]);

$context->httpResponse()
        ->status(204)
        ->send();
