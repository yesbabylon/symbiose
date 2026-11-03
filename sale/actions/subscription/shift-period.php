<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\subscription\Subscription;

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
    'providers'   => ['context']
]);

/**
 * @var \equal\php\Context $context
 */
$context = $providers['context'];

$subscription = Subscription::id($params['id'])
    ->read(['id'])
    ->first();

if(!$subscription) {
    throw new Exception('unknown_subscription', EQ_ERROR_UNKNOWN_OBJECT);
}

Subscription::id($subscription['id'])->do('shift_period');

$context->httpResponse()
        ->status(204)
        ->send();
