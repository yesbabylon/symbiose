<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Booking;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	'Provide a fully loaded tree for a given booking.',
    'params' 		=>	[
        'id' => [
            'description'   => 'Identifier of the booking for which the tree is requested.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'protected',
        'groups'            => ['booking.default.user']
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context']
]);

list($context) = [$providers['context']];


$tree = [
    'id', 'name', 'created', 'date_from', 'date_to', 'status', 'total', 'price',
    'customer_id' => [
        'id', 'rate_class_id'
    ],
    'center_id' => [
        'id', 'name', 'sojourn_type_id', 'product_groups_ids'
    ],
    'booking_lines_groups_ids' => [
        'id', 'name', 'order', 'has_pack', 'total', 'price', 'is_locked', 'is_autosale', 'is_extra', 'date_from', 'date_to', 'nb_pers', 'nb_nights', 'is_sojourn',
        'sojourn_type_id',
        'pack_id' => ['id', 'name'], 
        'rate_class_id' => ['id', 'name', 'description'],        
        'accomodations_ids' => [
            'id', 'booking_line_group_id', 
            'product_id' => [
                'id', 'name'
            ], 
            'rental_unit_assignments_ids' => [
                'id', 'qty', 
                'rental_unit_id' => [
                    'id', 'name', 'capacity'
                ]
            ]
        ],
        'booking_lines_ids' => [
            'id',
            'name', 'order', 'qty', 'vat_rate', 'unit_price', 'total', 'price', 'qty_vars', 'qty_accounting_method', 'is_rental_unit', 'is_accomodation', 'is_meal', 'free_qty', 'discount', 
            'price_id', 
            'product_id' => [
                'name', 'sku', 'product_model_id' => ['has_duration', 'duration']
            ],
            'auto_discounts_ids' => [
                'id', 'type', 'value', 
                'discount_id' => ['name'], 
                'discount_list_id' => ['name', 'rate_min', 'rate_max'] 
            ],
            'manual_discounts_ids' => [
                'id', 'type', 'value', 
                'discount_id' => ['name'] 
            ]
        ]
    ]
];


$bookings = Booking::id($params['id'])->read($tree)->adapt('txt')->get(true);

if(!$bookings || !count($bookings)) {
    throw new Exception('unknown_booking', QN_ERROR_UNKNOWN_OBJECT);
}

$booking = reset($bookings);

$context->httpResponse()
        ->body($booking)
        ->send();