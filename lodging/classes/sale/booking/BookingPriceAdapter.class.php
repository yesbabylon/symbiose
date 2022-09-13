<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class BookingPriceAdapter extends \sale\booking\BookingPriceAdapter {

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
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the adapter relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Booking Line Group the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ],

            'booking_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'description'       => 'Booking Line the adapter relates to, if any.',
                'ondelete'          => 'cascade'
            ]

        ];
    }


}