<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use inventory\service\Subscription;

list($params, $providers) = eQual::announce([
    'description' => 'Shift subscription dates depending on duration.',
    'params'      => [
        'id' =>  [
            'description' => 'ID of the subscription.',
            'type'        => 'integer',
            'required'    => true
        ]
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

$subscription = Subscription::id($params['id'])
    ->read([
        'id',
        'date_from',
        'date_to',
        'duration'
    ])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', QN_ERROR_UNKNOWN_OBJECT);
}

Subscription::id($subscription['id'])
    ->update([
        'date_from' => $subscription['date_to'],
        'date_to'   => strtotime(Subscription::MAP_DURATION[$subscription['duration']], $subscription['date_to'])
    ]);

$context->httpResponse()
        ->status(204)
        ->send();
