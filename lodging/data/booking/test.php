<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\orm\Operation;


list($params, $providers) = announce([
    'description'   => "Retrieve the consumptions attached to rental units of specified centers for a given time range.",
    'params'        => [],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


$collection = [
    [
        'id'    => 1,
        'test'  => 2
    ],
    [
        'id'    => 2,
        'test'  => 3
    ],
    [
        'id'    => 3,
        'test'  => 5
    ],
    [
        'id'    => 4,
        'test'  => 7
    ]
];

// standard deviation
$op =   [   '^', 
            [   '/', 
                [
                    'SUM', 
                    [
                        '^', 
                        ['-', 'object.test', ['AVG', 'object.test'] ], 
                        2
                    ]
                ], 
                ['-', ['COUNT', 'object.test'], 1]
            ],
            .5
        ];

/* 
$op =   [
            'SUM',
            ['+', 'object.test', 'object.id']
        ];
$op = ['SUM', 'object.test'];
$op = ['COUNT', 'object.test'];
$op = ['AVG', 'object.test'];
*/


$operation = new Operation($op);

$res =  $operation->compute($collection);

echo $res;
