<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Funding;

list($params, $providers) = announce([
    'description'   => "Checks that a given funding has been paid (should be scheduled on due_date).",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the funding to check.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'private'
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
list($context, $orm, $auth, $dispatch) = [ $providers['context'], $providers['orm'], $providers['auth'], $providers['dispatch'] ];

// switch to root account (access is 'private')
$auth->su();

$funding = Funding::id($params['id'])->read(['id', 'is_paid', 'due_date', 'booking_id'])->first();

if(!$funding) {
    throw new Exception("unknown_funding", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$funding['is_paid']) {

    // dispatch a message for notifying users
    $dispatch->dispatch('lodging.booking.payments', 'lodging\sale\booking\Booking', $funding['booking_id'], 'warning');

    // #todo - send a reminder to the customer

}


$context->httpResponse()
        ->status(204)
        ->send();