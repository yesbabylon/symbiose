<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;
use lodging\sale\booking\Funding;

list($params, $providers) = announce([
    'description'   => "Checks if all due payments have been received for given booking.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking the check against payments.',
            'type'          => 'integer',
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $orm, $auth, $dispatch) = [ $providers['context'], $providers['orm'], $providers['auth'], $providers['dispatch']];

// ensure booking object exists and is readable
$booking = Booking::id($params['id'])->read(['id', 'name', 'center_office_id', 'fundings_ids'])->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

$fundings = Funding::ids($booking['fundings_ids'])->read(['due_date', 'is_paid'])->get();

/*
    This controller is a check: an empty response means that no alert was raised
*/
$result = [];
$httpResponse = $context->httpResponse()->status(200);

$is_paid = true;
if($fundings) {
    $today = time();
    foreach($fundings as $fid => $funding) {
        if($funding['due_date'] <= $today && !$funding['is_paid']) {
            $is_paid = false;
            $result[] = $fid;
            break;
        }
    }
}

if(!$is_paid) {
    // by convention we dispatch an alert that relates to the controller itself.
    $dispatch->dispatch('lodging.booking.payments', 'lodging\sale\booking\Booking', $params['id'], 'important', 'lodging_booking_check-payments', ['id' => $params['id']],[],null,$booking['center_office_id']);
    $httpResponse->status(qn_error_http(QN_ERROR_NOT_ALLOWED));
}
else {
    // symetrical removal of the alert (if any)
    $dispatch->cancel('lodging.booking.payments', 'lodging\sale\booking\Booking', $params['id']);
}

$httpResponse->body($result)
             ->send();