<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
list($params, $providers) = announce([
    'description'   => "Returns a map with the descriptors of the installed apps (depending on initialized packages).",
    'params'        => [
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 */
list($context, $orm) = [$providers['context'], $providers['orm']];

$res = $orm->transition('core\User', 23, 'validation');

$context->httpResponse()
    ->body(['result' => $res])
    ->send();
