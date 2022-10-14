<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = announce([
    'description'   => 'Update hook for Bookings: makes additional checks and relay to model update controller.',
    'extends'       => 'core_model_update',
    'params'        => [
       'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to return (e.g. \'core\\User\').',
            'type'          => 'string',
            'required'      => true
        ],
        'id' =>  [
            'description'   => 'Unique identifier of the object to update.',
            'type'          => 'integer',
            'default'       => 0
        ],
        'ids' =>  [
            'description'   => 'List of Unique identifiers of the objects to update.',
            'type'          => 'array',
            'default'       => []
        ],
        'fields' =>  [
            'description'   => 'Associative array mapping fields to be updated with their related values.',
            'type'          => 'array',
            'default'       => []
        ],
        'force' =>  [
            'description'   => 'Flag for forcing update in case a concurrent change is detected.',
            'type'          => 'boolean',
            'default'       => false
        ],
        'lang' => [
            'description '  => 'Specific language for multilang field.',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'access' => [
        'visibility'        => 'protected'
    ],
    'providers'     => [ 'context' ]
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context) = [ $providers['context'] ];

if(isset($params['id']) && $params['id'] > 0) {
    $booking_id = $params['id'];
}
elseif(isset($params['ids']) && count($params['ids'])) {
    $booking_id = $params['ids'][0];
}
elseif(isset($params['fields']['id'])) {
    $booking_id = $params['fields']['id'];
}
else {
    throw new Exception("missing_object_identifier", QN_ERROR_INVALID_PARAM);
}

/*
    This controller is meant to intercept booking creation.
    We run a series of checks: each of those raises an Exception not passing.
*/

// 1) update the booking according to the received data

$result = eQual::run('do', 'model_update', $params, true);


// 2) check customer history

// check customer's previsous bookings for remaining unpaid amount
eQual::run('do', 'lodging_booking_check-customer-debtor', ['id' => $booking_id]);

// check customer's history for damages, slow payment or harm caused during previous bookings
eQual::run('do', 'lodging_booking_check-customer-history', ['id' => $booking_id]);


$context->httpResponse()
        ->body($result)
        ->send();