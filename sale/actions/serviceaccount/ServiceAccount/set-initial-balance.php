<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description' => 'Set the effective balance before the first service account report is created.',
    'params'      => [
        'id' => [
            'description'    => 'Identifier of the targeted service account.',
            'type'           => 'many2one',
            'foreign_object' => 'sale\serviceaccount\ServiceAccount',
            'default'        => 0
        ],
        'value' => [
            'description' => 'Initial effective balance.',
            'type'        => 'float',
            'required'    => true
        ]
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context', 'self']
]);

/** @var \equal\php\Context $context */
['context' => $context, 'self' => $self] = $providers;

if(!isset($params['id']) || $params['id'] <= 0) {
    throw new Exception('invalid_service_account', EQ_ERROR_INVALID_PARAM);
}

$self->do('set_initial_balance', ['value' => $params['value']]);

$context->httpResponse()
    ->status(204)
    ->send();
