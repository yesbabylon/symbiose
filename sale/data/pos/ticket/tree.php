<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\pos\CashdeskSession;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Provide a fully loaded tree for a given CashdeskSession (with establishment info).",
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the session for which the tree is requested.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['pos.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

list($context) = [$providers['context']];

$tree = [
    'id',
    'amount',
    'user_id',
    'cashdesk_id' => [
        'center_id' => [
            'name',
            'phone',
            'email',
            'organisation_id' => [
                'legal_name',
                'phone',
                'email',
                'vat_number'
            ],
            'center_office_id' => [
                'name',
                'address_street',
                'address_city',
                'address_zip'
            ]
        ]
    ] 
];

$cashdesksessions = CashdeskSession::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$cashdesksessions || !count($cashdesksessions)) {
    throw new Exception("unknown_session", QN_ERROR_UNKNOWN_OBJECT);
}

$cashdesksession = reset($cashdesksessions);

$context->httpResponse()
        ->body($cashdesksession)
        ->send();