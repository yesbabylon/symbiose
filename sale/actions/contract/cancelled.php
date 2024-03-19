<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\contract\Contract;

list($params, $providers) = announce([
    'description'   => "Sets contract as cancelled.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the contract to cancel.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'groups'            => ['admins'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];



Contract::id($params['id'])->update(['status' => 'cancelled']);


$context->httpResponse()
        ->status(204)
        ->send();