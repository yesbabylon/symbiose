<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = announce([
    'description'   => 'Create receivables from given time entries.',
    'params'        => [
        'ids' =>  [
            'description'    => 'Identifier of the targeted reports.',
            'type'           => 'one2many',
            'foreign_object' => 'timetrack\TimeEntry',
            'required'       => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm']
]);

list($context, $orm) = [$providers['context'], $providers['orm']];

eQual::run(
    'do',
    'sale_saleentry_add-receivables',
    [
        'ids'              => $params['ids'],
        'allow_multi_sale' => true
    ]
);

$context->httpResponse()
    ->status(204)
    ->send();
