<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\contract\Contract;

use core\Task;

list($params, $providers) = announce([
    'description'   => "Mark a contract as sent to the customer.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted contract.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)		
        'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']     
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// read contract object
$contract = Contract::id($params['id'])
                  ->read(['id', 'name', 'status', 'valid_until'])
                  ->first();
                  
if(!$contract) {
    throw new Exception("unknown_contract", QN_ERROR_UNKNOWN_OBJECT);
}

if($contract['status'] != 'pending') {
    throw new Exception("invalid_status", QN_ERROR_NOT_ALLOWED);
}

// Update booking status
Contract::id($params['id'])->update(['status' => 'sent']);


// #todo - check if required payment have been paid in the meantime

$context->httpResponse()
        ->status(204)
        ->send();