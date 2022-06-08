<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\Consumption;
use lodging\sale\booking\Contract;

list($params, $providers) = announce([
    'description'   => "Checks if a signed version of the contract has been received.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking the check against unit contract validity.',
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

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'status', 'has_contract', 'contracts_ids'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

/*
    This controller is a check: an empty response means that no alert was raised
*/
$result = [];
$httpResponse = $context->httpResponse()->status(200);


if(!$booking['has_contract'] || empty($booking['contracts_ids'])) {
    $status = 'unknown';
}
else {
    // by convention the most recent contract is listed first (see schema in lodging/classes/sale/booking/Booking.class.php)
    $contract_id = array_shift($booking['contracts_ids']);
    $contract = Contract::id($contract_id)->read(['status'])->first();
    $status = $contract['status'];
}


if($status != 'signed') {
    $result[] = $booking['id'];

    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.contract.unsigned', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-contract', ['id' => $params['id']]);

    $httpResponse->status(qn_error_http(QN_ERROR_MISSING_PARAM));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.contract.unsigned', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();