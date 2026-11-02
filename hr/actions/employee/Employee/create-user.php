<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

[$params, $providers] = eQual::announce([
    'description'   => 'Create a User account for a single Employee or a group of Employees.',
    'params'        => [
        'id' =>  [
            'type'              => 'many2one',
            'description'       => 'The specific Employee the request refers to.',
            'foreign_object'    => 'hr\employee\Employee'
        ],
        'ids' => [
            'type'              => 'one2many',
            'foreign_object'    => 'hr\employee\Employee',
            'description'       => 'List of Employees the request refers to.',
            'default'           => []
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'access' => [
        'visibility'    => 'protected',
        'groups'        => ['admins', 'operators']
    ],
    'providers'     => ['context', 'self']
]);

/** @var \equal\php\Context $context
 *  @var \equal\orm\Collection $self
*/
['context' => $context, 'self' => $self] = $providers;

$self->do('create_user_account');

$context->httpResponse()
    ->status(201)
    ->send();
