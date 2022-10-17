<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class BookingPriceAdapter extends Model {

    public static function getName() {
        return "Price Adapter";
    }

    public static function getDescription() {
        return "Adapters allow to adapt the final price of the booking lines, either by performing a direct computation, or by using a discount definition.";
    }

    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the adapter relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'description'       => 'Booking Line Group the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingLine',
                'description'       => 'Booking Line the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ],

            'is_manual_discount' => [
                'type'              => 'boolean',
                'description'       => "Flag to set the adapter as manual or related to a discount.",
                'default'           => true
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'percent',
                    'amount',
                    'freebie'
                ],
                'description'       => 'Type of manual discount (fixed amount or percentage of the price).',
                'visible'           => ['is_manual_discount', '=', true],
                'default'           => 'percent',
                'onupdate'          => 'onupdateValue'
            ],

            // #memo - important: to allow the maximum flexibility, percent values can hold 4 decimal digits (must not be rounded, except for display)
            'value' => [
                'type'              => 'float',
                'usage'             => 'amount/rate',
                'description'       => "Value of the discount (monetary amount or percentage).",
                'visible'           => ['is_manual_discount', '=', true],
                'default'           => 0.0,
                'onupdate'          => 'onupdateValue'
            ],

            'discount_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\Discount',
                'description'       => 'Discount related to the adapter, if any.',
                'visible'           => ['is_manual_discount', '=', false]
            ],

            'discount_list_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\discount\DiscountList',
                'description'       => 'Discount List related to the adapter, if any.',
                'visible'           => ['is_manual_discount', '=', false]
            ]
        ];
    }

    public static function onupdateValue($om, $oids, $values, $lang) {
        // reset computed price for related bookings and booking_line_groups
        $discounts = $om->read(__CLASS__, $oids, ['booking_id', 'booking_line_id', 'booking_line_group_id']);

        if($discounts > 0) {
            $bookings_ids = array_map( function($a) { return $a['booking_id']; }, $discounts);
            $booking_lines_ids = array_map( function($a) { return $a['booking_line_id']; }, $discounts);
            $booking_line_groups_ids = array_map( function($a) { return $a['booking_line_group_id']; }, $discounts);
            $om->update(Booking::getType(), $bookings_ids, ['price' => null, 'total' => null]);
            $om->callonce(BookingLine::getType(), '_resetPrices', $booking_lines_ids, [], $lang);
            $om->callonce(BookingLineGroup::getType(), '_resetPrices', $booking_line_groups_ids, [], $lang);
        }
    }

    public static function getConstraints() {
        return [
            'booking_line_id' =>  [
                'missing_relation' => [
                    'message'       => 'booking_line_id or booking_line_group_id must be set.',
                    'function'      => function ($booking_line_id, $values) {
                        return ($values['booking_line_id'] >= 0 || $values['booking_line_group_id'] >=0);
                    }
                ]
            ]
        ];
    }

}