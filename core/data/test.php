<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = eQual::announce([
    'description'   => 'Retrieve current permission that a user has on a given entity.',
    'providers'     => ['context'],
    'response'      => [
        'content-type'  => 'text/plain',
        'charset'       => 'utf-8',
        'accept-origin' => '*',
        'cacheable'     => true,
        'cache-vary'    => ['uri'],
        'expires'       => 60
    ]
]);


$context->httpResponse()
        ->status(200)
        ->body('hello word !')
        ->send();