<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\order\Order;
use sale\order\Contract;

list($params, $providers) = eQual::announce([
    'description'   => "Checks if a signed version of the contract has been received.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the order the check against unit contract validity.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'response'      => [
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

$order = Order::id($params['id'])->read(['id', 'status', 'has_contract', 'contracts_ids'])->first(true);

if(!$order) {
    throw new Exception("unknown_order", QN_ERROR_UNKNOWN_OBJECT);
}

$result = [];
$httpResponse = $context->httpResponse()->status(200);

if(!$order['has_contract'] || empty($order['contracts_ids'])) {
    $status = 'unknown';
    $contract_id = 0;
}
else {
    $contract_id = array_shift($order['contracts_ids']);
    $contract = Contract::id($contract_id)->read(['status'])->first(true);
    $status = $contract['status'];
}

if($status != 'signed') {
    $result[] = $order['id'];
    $httpResponse->status(qn_error_http(QN_ERROR_MISSING_PARAM));
}

$httpResponse->body($result)
             ->send();