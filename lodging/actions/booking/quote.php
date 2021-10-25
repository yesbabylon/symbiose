<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Booking;

list($params, $providers) = announce([
    'description'   => "Revert a booking to 'quote' status: booking is visible but no rental units are reserved.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ]
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
                  ->read(['id', 'name', 'booking_lines_ids' => 'consumptions_ids'])
                  ->first();
                  
if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

// remove existing consumptions
foreach($booking['booking_lines_ids'] as $lid => $line) {
    $consumptions_ids = array_map(function($a) { return "-$a";}, $line['consumptions_ids']);
    BookingLine::id($lid)->update(['consumptions_ids' => $consumptions_ids]);
}

// Update booking status
Booking::id($params['id'])->update(['status' => 'quote']);


$context->httpResponse()
        ->status(200)
        ->body([])
        ->send();