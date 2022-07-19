<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\pos\CashdeskSession;
use sale\pos\Order;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Provide a fully loaded tree for a given CashdeskSession (Establishment info's).",
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the order for which the tree is requested.',
            'type'          => 'integer',
            'required'      => true
        ],
        'variant' =>  [
            'description'   => 'Type of tree being requested.',
            'type'          => 'string',
            'selection'     => [
                'session'        
            ],
            'default'       => 'lines'
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


$tree = [];

switch($params['variant']) {
    case 'session':
        $tree = [
            'id',
            'amount',
            'user_id',
            'cashdesk_id'=>[
                'center_id'=>[
                    'name',
                    'phone',
                    'email',
                    'organisation_id'=>[
                        'legal_name',
                        'phone',
                        'email',
                        'vat_number'
                    ],
                    'center_office_id'=>[
                        'name',
                        'address_street',
                        'address_city',
                        'address_zip'
                    ]

                ]
            ] 
        ];
        break;
}

$cashdesksessions = CashdeskSession::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$cashdesksessions || !count($cashdesksessions)) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$cashdesksession = reset($cashdesksessions);


$context->httpResponse()
        ->body($cashdesksession)
        ->send();