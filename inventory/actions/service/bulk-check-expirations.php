<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/


// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Bulk check subscription expiration.",
    'params' 		=>	[
        'ids' => [
            'description'       => 'List of Subscription identifiers the check against emptyness.',
            'type'              => 'array'
        ]
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

list($context) = [$providers['context']];

foreach($params['ids'] as $id) {
    eQual::run('do', 'inventory_service_check-expiration', ['id' => $id]);
}

$context->httpResponse()
        ->status(204)
        ->send();