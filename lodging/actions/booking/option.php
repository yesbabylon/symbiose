<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\realestate\RentalUnit;
use sale\booking\Composition;
use sale\booking\CompositionItem;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Generate the composition (hosts listing) for a given booking. If a composition already exists, it is reset.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];



Booking::id($params['id'])->update(['status' => 'option']);


$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();