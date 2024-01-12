<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\User;
use timetrack\TimeEntry;

list($params, $providers) = eQual::announce([
    'description' => 'Create an empty time entry.',
    'params'      => [
        'user_id' => [
            'description' => 'ID of the user who realised that time entry.',
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

$context = $providers['context'];

$user = User::id($params['user_id'])
    ->read(['id'])
    ->first();

if(!isset($user)) {
    throw new Exception('unknown_user', QN_ERROR_UNKNOWN_OBJECT);
}

TimeEntry::create([
    'name'    => 'New entry '.date('Y-m-d H:m:s', time()),
    'user_id' => $params['user_id']
]);

$context->httpResponse()
        ->status(204)
        ->send();
