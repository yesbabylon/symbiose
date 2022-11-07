<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\BookingLineGroup;
use lodging\sale\catalog\Product;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Updates a soujourn based on partial patch of the main product. This script is meant to be called by the `booking/services` UI.",
    'params' 		=>	[
        'id' =>  [
            'description'       => 'Identifier of the targeted sojourn.',
            'type'              => 'many2one',
            'foreign_object'    => \lodging\sale\booking\BookingLineGroup::getType(),
            'required'          => true
        ],
        'product_id' =>  [
            'type'              => 'many2one',
            'description'       => 'Identitifer of the product to assign the sojourn to.',
            'foreign_object'    => \lodging\sale\catalog\Product::getType(),
            'default'           => false
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
    'providers' => ['context', 'orm']
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm']];

// read BookingLineGroup object
$group = BookingLineGroup::id($params['id'])
    ->read([
        'is_extra',
        'booking_id' => ['id', 'status'],
    ])
    ->first(true);

if(!$group) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if(!in_array($group['booking_id']['status'], ['quote', 'checkedout']) && !$group['is_extra']) {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// find targeted product
$product = Product::id($params['product_id'])
    ->read([
        'name',
        'is_pack',
        'pack_lines_ids' => ['child_product_model_id' => ['is_rental_unit', 'is_accomodation']]
    ])
    ->first(true);

if(!$product) {
    throw new Exception("unknown_product", QN_ERROR_UNKNOWN_OBJECT);
}

// update group according to the services it holds (sojourn, event, or arbitrary)
foreach($product['pack_lines_ids'] as $lid => $line) {
    if($line['child_product_model_id']['is_accomodation']) {
        $is_sojourn = true;
        BookingLineGroup::id($params['id'])->update(['is_sojourn' => true]);
        break;
    }
    elseif($line['child_product_model_id']['is_accomodation']) {
        BookingLineGroup::id($params['id'])->update(['is_event' => true]);
        break;
    }
}

// update group name
BookingLineGroup::id($params['id'])->update(['name' => $product['name']]);

// empty group's BookingLines, if any
BookingLine::search(['booking_line_group_id', '=', $params['id']])->delete(true);

// assign a Pack if the product is a pack
if($product['is_pack']) {
    BookingLineGroup::id($params['id'])
        ->update(['has_pack'=> true])
        ->update(['pack_id' => $params['product_id']]);
}
// assign a single product line otherwise
else {
    BookingLine::create([
            'order'                 => 1,
            'booking_id'            => $group['booking_id']['id'],
            'booking_line_group_id' => $params['id']
        ])
        ->update([
            'product_id'            => $params['product_id']
        ]);
}


$context->httpResponse()
        ->status(204)
        ->send();