<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Booking;
use lodging\sale\booking\Contract;

use core\Task;

list($params, $providers) = announce([
    'description'   => "Cancels a booking, whatever its current status. Adjust balance is cancellation fees are applied.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['booking.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']     
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// read booking object
$booking = Booking::id($params['id'])
                  ->read(['id', 'name', 'is_cancelled', 'status', 'contracts_ids', 'booking_lines_ids' => 'consumptions_ids', 'fundings_ids' => ['id', 'is_paid']])
                  ->first();
                  
if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

// booking already cancelled
if($booking['is_cancelled']) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// revert booking to quote 
$json = run('do', 'lodging_booking_quote', ['id' => $params['id']]);
$data = json_decode($json, true);
if(isset($data['errors'])) {
    // raise an exception with returned error code
    foreach($data['errors'] as $name => $message) {
        throw new Exception($message, qn_error_code($name));
    }
}

// mark the booking as cancelled
Booking::id($params['id'])->update(['is_cancelled' => true]);

// #todo : if cancellation fees are applicable, set status to 'due_balance'


// if booking is balanced, set status to balanced and archive booking
Booking::id($params['id'])->update(['status' => 'balanced', 'state' => 'archive']);


$context->httpResponse()
        ->status(200)
        ->body([])
        ->send();