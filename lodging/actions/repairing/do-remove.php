<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Repairing;

list($params, $providers) = announce([
    'description'   => "This will remove the repairing episode.The rental unit will be released and made available for bookings.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted repairing.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']     
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


Repairing::id($params['id'])->delete(true);

$context->httpResponse()
        // success but notify client to reset content
        ->status(205)
        ->send();