<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = eQual::announce([
    'description' => 'Advanced search for billable Time Entries: returns a collection of Reports according to extra parameters.',
    'extends'     => 'timetrack_timeentry-collect',
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'   => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$result = eQual::run('get', 'timetrack_timeentry-collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();
