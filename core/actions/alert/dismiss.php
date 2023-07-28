<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = eQual::announce([
    'description'   => "Tries to dismiss a message. Should be invoked as a user request for removing the message. If the situation is still occuring an identical alert will be re-created.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the alert to dismiss.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth', 'dispatch']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 * @var \equal\auth\AuthenticationManager $auth
 * @var \equal\dispatch\Dispatcher $dispatch
 */
list($context, $orm, $auth, $dispatch) = [ $providers['context'], $providers['orm'], $providers['auth'], $providers['dispatch']];

// #todo - restrict access based on link between message model and user groups

// #todo - deprecate (visibility = protected)
$user_id = $auth->userId();

if($user_id <= 0) {
    throw new Exception("not_allowed", QN_ERROR_NOT_ALLOWED);
}

// If the alert is not found, the call is ignored. If a controller is mentioned in the alert it is called.
$dispatch->dismiss($params['id']);

$context->httpResponse()
        ->status(200)
        ->send();