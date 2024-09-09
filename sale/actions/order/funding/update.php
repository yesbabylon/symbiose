<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\order\Funding;

list($params, $providers) = eQual::announce([
    'description' => 'Remove funding not paid and update the partially paid amount for the order.',
    'params'      => [
        'id' =>  [
            'description'       => 'ID of the funding.',
            'type'              => 'integer'
        ],
        'ids' =>  [
            'description'       => 'Identifiers of the targeted invoices.',
            'type'              => 'one2many',
            'foreign_object'    => 'sale\order\Funding',
            'default'           => []
        ],
    ],
    'response'    => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $dispatch) = [ $providers['context'], $providers['dispatch']];


if(empty($params['ids'])) {
    if(!isset($params['id']) || $params['id'] <= 0) {
        throw new Exception('funding_invalid_id', QN_ERROR_INVALID_PARAM);
    }

    $params['ids'][] = $params['id'];
}

$fundings = Funding::id($params['ids'])
    ->read([
        'due_amount',
        'paid_amount',
        'is_paid'
    ])
    ->get(true);

foreach($fundings as $funding) {
    if(round($funding['paid_amount'], 2) == 0 && !$funding['is_paid']) {
        Funding::id($funding['id'])->delete(true);
    }
    else {
        Funding::id($funding['id'])->update(['due_amount' => $funding['paid_amount']]);
    }
}


$context->httpResponse()
    ->status(204)
    ->send();