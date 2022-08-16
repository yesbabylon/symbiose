<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLineRentalUnitAssignement;

list($params, $providers) = announce([
    'description'   => "Sets booking as checked out.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)
        'groups'            => ['booking.default.user'],// list of groups ids or names granted
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\auth\AuthenticationManager   $auth
 */
list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

// mark booking as checked-out
Booking::id($params['id'])->update(['status' => 'checkedout']);

// mark involved rental_units as ready (no more customer occupying)
// retrieve accomodations assigned to the booking
$assignments_ids = BookingLineRentalUnitAssignement::search([
        ['booking_id', '=', $params['id']],
        ['is_accomodation', '=', true]
    ])
    ->update(['status' => 'empty', 'action_required' => 'cleanup_full']);

// #memo - now user can complete the booking with additional services, if any

$context->httpResponse()
        ->status(204)
        ->send();