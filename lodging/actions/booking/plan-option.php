<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\BookingLineGroup;
use lodging\sale\booking\SojournProductModel;
use lodging\sale\booking\SojournProductModelRentalUnitAssignement;

use lodging\realestate\RentalUnit;
use lodging\sale\catalog\Product;
use lodging\sale\catalog\ProductModel;
use sale\customer\Customer;

list($params, $providers) = announce([
    'description'   => "Create an option from the planning, by providing date range, customer and rental unit.",
    'params'        => [

        'date_from' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'date',
            'required'      => true
        ],

        'date_to' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'date',
            'required'      => true
        ],

        'customer_identity_id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'required'      => true
        ],

        'rental_unit_id' =>  [
            'description'   => 'Identifier of the targeted booking.',
            'type'          => 'integer',
            'required'      => true
        ],

        'no_expiry' =>  [
            'description'   => 'The option will remain active without time limit.',
            'type'          => 'boolean',
            'default'       => false
        ],

        'free_rental_units' =>  [
            'description'   => 'At expiration of the option, automatically release reserved rental units, if any.',
            'type'          => 'boolean',
            'default'       => false
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user']
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron', 'dispatch']
]);

/**
 * @var \equal\php\Context                  $context
 * @var \equal\orm\ObjectManager            $orm
 * @var \equal\cron\Scheduler               $cron
 * @var \equal\dispatch\Dispatcher          $dispatch
 */
list($context, $orm, $cron, $dispatch) = [$providers['context'], $providers['orm'], $providers['cron'], $providers['dispatch']];


/*
    Check consistency of parameters
*/

// retrieve rental unit and related center
$rental_unit = RentalUnit::id($params['rental_unit_id'])
    ->read(['id', 'name', 'capacity', 'center_id' => ['id', 'name', 'sojourn_type_id', 'product_groups_ids']])
    ->first();

if(!$rental_unit) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

// look for an existing customer for given identity
$customer = Customer::search([ ['relationship', '=', 'customer'], ['owner_identity_id', '=', 1], ['partner_identity_id', '=', $params['customer_identity_id']] ])
    ->read(['rate_class_id', 'customer_nature_id'])
    ->first();

// default nature (individual)
$customer_nature_id = 30;
// default rate class (general public)
$rate_class_id = 4;

if($customer) {
    $customer_nature_id = $customer['customer_nature_id'];
    $rate_class_id = $customer['rate_class_id'];
}

// look for an active product whose model explicitely relates to the rental unit(rental_unit_id)
$product_models_ids = ProductModel::search([ ['can_sell', '=', true], ['is_rental_unit', '=', true], ['rental_unit_id', '=', $params['rental_unit_id']] ])->ids();

if(!count($product_models_ids)) {
    // no direct product found : look for an active product for given center relating to is_accomodation
    if($rental_unit['center_id']['product_groups_ids']) {
        $domain = [
            ["groups_ids", "contains", $rental_unit['center_id']['product_groups_ids'][0]],
            ['can_sell', '=', true],
            ['is_accomodation', '=', true]
        ];
        $product_models_ids = ProductModel::search($domain)->ids();
    }
    if(!count($product_models_ids)) {
        throw new Exception("no_product_match", QN_ERROR_UNKNOWN_OBJECT);
    }
}

$product = Product::search([ ['can_sell', '=', true], ['product_model_id', 'in', $product_models_ids] ])
    ->read(['id', 'product_model_id'])
    ->first();

if(!$product) {
    throw new Exception("no_product_match", QN_ERROR_UNKNOWN_OBJECT);
}

/*
	If a match has been found, create a booking
    #memo - some product models are not bound to specific rental units, so another RU matching the specifics might be selected
*/
$booking = Booking::create([
        'date_from'             => $params['date_from'],
        'date_to'               => $params['date_to'],
        'center_id'             => $rental_unit['center_id']['id'],
        'customer_identity_id'  => $params['customer_identity_id'],
        'customer_nature_id'    => $customer_nature_id
    ])
    ->first();

$groups = BookingLineGroup::create([
    'booking_id'            => $booking['id'],
    'name'                  => 'Séjour '.$rental_unit['center_id']['name'],
    'rate_class_id'         => $rate_class_id,
    'sojourn_type_id'       => $rental_unit['center_id']['sojourn_type_id'],
    'is_sojourn'            => true,
    'date_from'             => $params['date_from'],
    'date_to'               => $params['date_to']
]);

$group = $groups->first();

BookingLine::create([
        'booking_id'            => $booking['id'],
        'booking_line_group_id' => $group['id']
    ])
    ->update([
        'product_id'            => $product['id']
    ]);

$groups->update([
    'nb_pers'                   => $rental_unit['capacity']
]);



// make sure computed fields are available
Booking::id($booking['id'])->read(['name', 'status', 'date_from', 'date_to', 'total', 'price']);

$group = $groups->read(['id', 'sojourn_product_models_ids'])->first();

// reset auto assigned rental units (if any)
SojournProductModel::ids($group['sojourn_product_models_ids'])->delete(true);

// force assigning selected rental unit (we know current product matches the received rental unit)
$spm = SojournProductModel::create([
        'booking_id'            => $booking['id'],
        'booking_line_group_id' => $group['id'],
        'product_model_id'      => $product['product_model_id']
    ])
    ->first();

SojournProductModelRentalUnitAssignement::create([
        'booking_id'                => $booking['id'],
        'booking_line_group_id'     => $group['id'],
        'sojourn_product_model_id'  => $spm['id'],
        'rental_unit_id'            => $rental_unit['id'],
        'qty'                       => $rental_unit['capacity']
    ]);



/*
    Try to set the booking status to 'option'
*/

// might raise 'overbooking_detected'
eQual::run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);


$context->httpResponse()
        ->status(204)
        ->send();